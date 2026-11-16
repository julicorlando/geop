package br.com.shoppingcarpina.geopportaria;

import android.Manifest;
import android.annotation.SuppressLint;
import android.app.Activity;
import android.app.AlertDialog;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.content.SharedPreferences;
import android.content.pm.PackageManager;
import android.graphics.Color;
import android.graphics.Typeface;
import android.media.AudioManager;
import android.media.ToneGenerator;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.VibrationEffect;
import android.os.Vibrator;
import android.text.InputType;
import android.util.TypedValue;
import android.view.Gravity;
import android.view.View;
import android.view.ViewGroup;
import android.view.WindowManager;
import android.webkit.CookieManager;
import android.webkit.PermissionRequest;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Button;
import android.widget.EditText;
import android.widget.FrameLayout;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import org.json.JSONObject;

/**
 * App da portaria (Sunmi P2 / P2-B e similares).
 *
 * Abre a tela "Portaria" do GEOP (/cftv/controle/), que lista as liberações e
 * solicitações aprovadas do dia e registra entrada/saída pela credencial (QR).
 * O código lido pelo leitor embutido da Sunmi chega por broadcast e é enviado
 * para a página; em aparelhos sem leitor, a câmera do WebView é liberada.
 */
public class MainActivity extends Activity {
    private static final String CAMINHO_PORTARIA = "/cftv/controle/";
    private static final String PREFS = "portaria";
    private static final String PREF_SERVIDOR = "servidor";
    private static final int PEDIDO_CAMERA = 31;

    // Leitores Sunmi (serviço com.sunmi.scanner) e o padrão usado por outros coletores.
    private static final String[][] ACOES_LEITOR = {
            {"com.sunmi.scanner.ACTION_DATA_CODE_RECEIVED", "data"},
            {"android.intent.ACTION_DECODE_DATA", "barcode_string"},
            {"com.android.server.scannerservice.broadcast", "scannerdata"},
    };

    private WebView webView;
    private ProgressBar progresso;
    private TextView titulo;
    private PermissionRequest pedidoCameraPendente;
    private ToneGenerator bip;
    private long ultimaLeitura;
    private String ultimoCodigo = "";

    private final BroadcastReceiver receptorLeitor = new BroadcastReceiver() {
        @Override
        public void onReceive(Context context, Intent intent) {
            if (intent == null || intent.getAction() == null) return;
            for (String[] acao : ACOES_LEITOR) {
                if (acao[0].equals(intent.getAction())) {
                    String codigo = intent.getStringExtra(acao[1]);
                    if (codigo == null) {
                        byte[] bruto = intent.getByteArrayExtra(acao[1]);
                        if (bruto != null) codigo = new String(bruto);
                    }
                    if (codigo != null) codigoLido(codigo.trim());
                    return;
                }
            }
        }
    };

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        getWindow().addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON);
        try {
            bip = new ToneGenerator(AudioManager.STREAM_NOTIFICATION, 80);
        } catch (RuntimeException ignored) {
            bip = null;
        }
        montarTela();
        configurarWebView();
        if (savedInstanceState != null) {
            webView.restoreState(savedInstanceState);
        } else {
            webView.loadUrl(urlPortaria());
        }
    }

    // ------------------------------------------------------------------ tela

    private int dp(int v) {
        return (int) TypedValue.applyDimension(TypedValue.COMPLEX_UNIT_DIP, v, getResources().getDisplayMetrics());
    }

    private Button botaoBarra(String texto, View.OnClickListener acao) {
        Button b = new Button(this, null, android.R.attr.borderlessButtonStyle);
        b.setText(texto);
        b.setTextColor(Color.WHITE);
        b.setTextSize(TypedValue.COMPLEX_UNIT_SP, 20);
        b.setMinWidth(dp(48));
        b.setPadding(dp(8), 0, dp(8), 0);
        b.setOnClickListener(acao);
        return b;
    }

    private void montarTela() {
        LinearLayout raiz = new LinearLayout(this);
        raiz.setOrientation(LinearLayout.VERTICAL);

        LinearLayout barra = new LinearLayout(this);
        barra.setOrientation(LinearLayout.HORIZONTAL);
        barra.setGravity(Gravity.CENTER_VERTICAL);
        barra.setBackgroundColor(Color.parseColor("#003B63"));
        barra.setPadding(dp(12), 0, dp(4), 0);

        titulo = new TextView(this);
        titulo.setText("GEOP · Portaria");
        titulo.setTextColor(Color.WHITE);
        titulo.setTypeface(Typeface.DEFAULT_BOLD);
        titulo.setTextSize(TypedValue.COMPLEX_UNIT_SP, 17);
        barra.addView(titulo, new LinearLayout.LayoutParams(0, ViewGroup.LayoutParams.WRAP_CONTENT, 1));
        barra.addView(botaoBarra("⌂", v -> webView.loadUrl(urlPortaria())));
        barra.addView(botaoBarra("⟳", v -> webView.reload()));
        barra.addView(botaoBarra("⚙", v -> configurarServidor()));
        raiz.addView(barra, new LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, dp(52)));

        FrameLayout area = new FrameLayout(this);
        webView = new WebView(this);
        area.addView(webView, new FrameLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT,
                ViewGroup.LayoutParams.MATCH_PARENT));
        progresso = new ProgressBar(this, null, android.R.attr.progressBarStyleHorizontal);
        progresso.setMax(100);
        FrameLayout.LayoutParams lp = new FrameLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, dp(4));
        lp.gravity = Gravity.TOP;
        area.addView(progresso, lp);
        raiz.addView(area, new LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, 0, 1));

        setContentView(raiz);
    }

    // ------------------------------------------------------------------ servidor

    private String servidor() {
        SharedPreferences p = getSharedPreferences(PREFS, MODE_PRIVATE);
        String s = p.getString(PREF_SERVIDOR, BuildConfig.SERVIDOR_PADRAO);
        return s.replaceAll("/+$", "");
    }

    private String urlPortaria() {
        return servidor() + CAMINHO_PORTARIA;
    }

    private String hostServidor() {
        return Uri.parse(servidor()).getHost();
    }

    private void configurarServidor() {
        final EditText campo = new EditText(this);
        campo.setInputType(InputType.TYPE_CLASS_TEXT | InputType.TYPE_TEXT_VARIATION_URI);
        campo.setText(servidor());
        campo.setSelectAllOnFocus(true);
        LinearLayout caixa = new LinearLayout(this);
        caixa.setPadding(dp(20), dp(8), dp(20), 0);
        caixa.addView(campo, new LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT,
                ViewGroup.LayoutParams.WRAP_CONTENT));
        new AlertDialog.Builder(this)
                .setTitle("Endereço do GEOP")
                .setMessage("Ex.: https://geop.shoppingcarpina.com.br\nVersão do app: " + BuildConfig.VERSION_NAME)
                .setView(caixa)
                .setPositiveButton("Salvar", (d, w) -> {
                    String url = campo.getText().toString().trim().replaceAll("/+$", "");
                    if (!url.startsWith("https://") || Uri.parse(url).getHost() == null) {
                        Toast.makeText(this, "Use um endereço https:// válido.", Toast.LENGTH_LONG).show();
                        return;
                    }
                    getSharedPreferences(PREFS, MODE_PRIVATE).edit().putString(PREF_SERVIDOR, url).apply();
                    webView.loadUrl(urlPortaria());
                })
                .setNeutralButton("Padrão", (d, w) -> {
                    getSharedPreferences(PREFS, MODE_PRIVATE).edit().remove(PREF_SERVIDOR).apply();
                    webView.loadUrl(urlPortaria());
                })
                .setNegativeButton("Cancelar", null)
                .show();
    }

    // ------------------------------------------------------------------ WebView

    @SuppressLint("SetJavaScriptEnabled")
    private void configurarWebView() {
        WebSettings s = webView.getSettings();
        s.setJavaScriptEnabled(true);
        s.setDomStorageEnabled(true);
        s.setMediaPlaybackRequiresUserGesture(false);
        s.setLoadWithOverviewMode(true);
        s.setUseWideViewPort(true);
        s.setMixedContentMode(WebSettings.MIXED_CONTENT_NEVER_ALLOW);
        s.setUserAgentString(s.getUserAgentString() + " GEOP-Portaria-Android/" + BuildConfig.VERSION_NAME);
        CookieManager.getInstance().setAcceptCookie(true);
        CookieManager.getInstance().setAcceptThirdPartyCookies(webView, false);

        webView.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                Uri u = request.getUrl();
                if ("https".equals(u.getScheme()) && hostServidor() != null && hostServidor().equalsIgnoreCase(u.getHost())) {
                    return false;
                }
                try {
                    startActivity(new Intent(Intent.ACTION_VIEW, u));
                } catch (Exception ignored) {
                    Toast.makeText(MainActivity.this, "Não foi possível abrir o link.", Toast.LENGTH_SHORT).show();
                }
                return true;
            }

            @Override
            public void onReceivedError(WebView view, WebResourceRequest request, WebResourceError error) {
                if (request.isForMainFrame()) mostrarErro(String.valueOf(error.getDescription()));
            }

            @Override
            public void onPageFinished(WebView view, String url) {
                CookieManager.getInstance().flush();
            }
        });

        webView.setWebChromeClient(new WebChromeClient() {
            @Override
            public void onProgressChanged(WebView view, int p) {
                progresso.setProgress(p);
                progresso.setVisibility(p >= 100 ? View.GONE : View.VISIBLE);
            }

            @Override
            public void onReceivedTitle(WebView view, String t) {
                if (t != null && !t.isEmpty() && !t.startsWith("http")) titulo.setText(t.split(" · ")[0]);
            }

            @Override
            public void onPermissionRequest(final PermissionRequest request) {
                runOnUiThread(() -> tratarPermissaoCamera(request));
            }
        });
    }

    private void tratarPermissaoCamera(PermissionRequest request) {
        Uri origem = request.getOrigin();
        boolean querCamera = false;
        for (String r : request.getResources()) {
            if (PermissionRequest.RESOURCE_VIDEO_CAPTURE.equals(r)) querCamera = true;
        }
        if (!querCamera || origem == null || hostServidor() == null || !hostServidor().equalsIgnoreCase(origem.getHost())) {
            request.deny();
            return;
        }
        if (checkSelfPermission(Manifest.permission.CAMERA) == PackageManager.PERMISSION_GRANTED) {
            request.grant(new String[]{PermissionRequest.RESOURCE_VIDEO_CAPTURE});
        } else {
            pedidoCameraPendente = request;
            requestPermissions(new String[]{Manifest.permission.CAMERA}, PEDIDO_CAMERA);
        }
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, String[] permissions, int[] grantResults) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults);
        if (requestCode != PEDIDO_CAMERA || pedidoCameraPendente == null) return;
        if (grantResults.length > 0 && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
            pedidoCameraPendente.grant(new String[]{PermissionRequest.RESOURCE_VIDEO_CAPTURE});
        } else {
            pedidoCameraPendente.deny();
            Toast.makeText(this, "Sem a câmera, use o leitor ou digite o código.", Toast.LENGTH_LONG).show();
        }
        pedidoCameraPendente = null;
    }

    private void mostrarErro(String detalhe) {
        String html = "<html><body style='font-family:sans-serif;text-align:center;padding:40px 20px;color:#0f172a'>"
                + "<h2>Sem conexão com o GEOP</h2><p style='color:#64748b'>Verifique a internet do aparelho.</p>"
                + "<p style='color:#94a3b8;font-size:13px'>" + android.text.Html.escapeHtml(detalhe) + "</p>"
                + "<p><a href='" + urlPortaria() + "' style='display:inline-block;padding:14px 28px;background:#003B63;"
                + "color:#fff;border-radius:8px;text-decoration:none;font-size:18px'>Tentar de novo</a></p></body></html>";
        webView.loadDataWithBaseURL(servidor(), html, "text/html", "utf-8", urlPortaria());
    }

    // ------------------------------------------------------------------ leitor de QR

    private void codigoLido(String codigo) {
        if (codigo.isEmpty()) return;
        long agora = System.currentTimeMillis();
        if (codigo.equals(ultimoCodigo) && agora - ultimaLeitura < 2500) return; // leitura repetida
        ultimoCodigo = codigo;
        ultimaLeitura = agora;
        avisar();
        String atual = webView.getUrl();
        if (atual != null && atual.contains(CAMINHO_PORTARIA)) {
            String js = "window.geopLerCodigo?window.geopLerCodigo(" + JSONObject.quote(codigo) + "):location.assign("
                    + JSONObject.quote(urlPortaria() + "?codigo=" + Uri.encode(codigo)) + ")";
            webView.evaluateJavascript(js, null);
        } else {
            webView.loadUrl(urlPortaria() + "?codigo=" + Uri.encode(codigo));
        }
    }

    private void avisar() {
        if (bip != null) bip.startTone(ToneGenerator.TONE_PROP_BEEP, 120);
        Vibrator v = (Vibrator) getSystemService(VIBRATOR_SERVICE);
        if (v != null && v.hasVibrator()) {
            if (Build.VERSION.SDK_INT >= 26) v.vibrate(VibrationEffect.createOneShot(60, VibrationEffect.DEFAULT_AMPLITUDE));
            else v.vibrate(60);
        }
    }

    // ------------------------------------------------------------------ ciclo de vida

    @SuppressLint("UnspecifiedRegisterReceiverFlag")
    @Override
    protected void onResume() {
        super.onResume();
        IntentFilter f = new IntentFilter();
        for (String[] acao : ACOES_LEITOR) f.addAction(acao[0]);
        if (Build.VERSION.SDK_INT >= 33) registerReceiver(receptorLeitor, f, Context.RECEIVER_EXPORTED);
        else registerReceiver(receptorLeitor, f);
        webView.onResume();
    }

    @Override
    protected void onPause() {
        try {
            unregisterReceiver(receptorLeitor);
        } catch (IllegalArgumentException ignored) {
            // não estava registrado
        }
        webView.onPause();
        CookieManager.getInstance().flush();
        super.onPause();
    }

    @Override
    protected void onDestroy() {
        if (bip != null) bip.release();
        webView.destroy();
        super.onDestroy();
    }

    @Override
    protected void onSaveInstanceState(Bundle outState) {
        super.onSaveInstanceState(outState);
        webView.saveState(outState);
    }

    @Override
    public void onBackPressed() {
        if (webView.canGoBack()) webView.goBack();
        else super.onBackPressed();
    }
}
