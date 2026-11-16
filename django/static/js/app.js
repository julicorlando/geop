/* GEOP — comportamento da interface (sem dependências). */
(function () {
  "use strict";

  // Menu lateral no celular
  document.addEventListener("click", function (e) {
    if (e.target.closest("[data-menu]")) { document.body.classList.toggle("menu-aberto"); return; }
    if (document.body.classList.contains("menu-aberto") && !e.target.closest(".sidebar")) document.body.classList.remove("menu-aberto");
  });

  // Confirmação antes de ações sensíveis e proteção contra duplo envio
  document.addEventListener("submit", function (e) {
    var form = e.target;
    var msg = form.getAttribute("data-confirmar");
    if (msg && !window.confirm(msg)) { e.preventDefault(); return; }
    if (form.dataset.processando === "1") { e.preventDefault(); return; }
    if (form.dataset.aguardando === "1") { e.preventDefault(); alert("Aguarde: as fotos ainda estão sendo preparadas."); return; }
    form.dataset.processando = "1";
    form.querySelectorAll("button[type=submit], button:not([type])").forEach(function (b) { b.disabled = true; });
    setTimeout(function () { form.dataset.processando = ""; form.querySelectorAll("button").forEach(function (b) { b.disabled = false; }); }, 15000);
  });

  // Visualização ampliada das fotos
  var lightbox = document.createElement("div");
  lightbox.className = "lightbox";
  lightbox.innerHTML = '<img alt="">';
  lightbox.addEventListener("click", function () { lightbox.classList.remove("aberto"); });
  document.addEventListener("keydown", function (e) { if (e.key === "Escape") lightbox.classList.remove("aberto"); });
  document.addEventListener("click", function (e) {
    var link = e.target.closest("[data-ampliar]");
    if (!link) return;
    e.preventDefault();
    if (!lightbox.isConnected) document.body.appendChild(lightbox);
    lightbox.querySelector("img").src = link.getAttribute("href");
    lightbox.classList.add("aberto");
  });

  // ------------------------------------------------------------------
  // Redução das fotos no próprio aparelho antes do envio.
  // Fotos de celular (4–12 MB) viram ~0,3–0,8 MB: o envio fica muito mais
  // rápido em 4G/Wi-Fi fraco. O servidor comprime novamente de qualquer forma.
  // ------------------------------------------------------------------
  var LADO_MAX = parseInt(document.documentElement.dataset.ladoMax || "1600", 10);
  var QUALIDADE = 0.85;

  function formatar(bytes) {
    if (bytes < 1024) return bytes + " B";
    if (bytes < 1048576) return (bytes / 1024).toFixed(0) + " KB";
    return (bytes / 1048576).toFixed(1).replace(".", ",") + " MB";
  }

  function carregar(arquivo) {
    if (window.createImageBitmap) {
      return createImageBitmap(arquivo, { imageOrientation: "from-image" }).catch(function () { return carregarImg(arquivo); });
    }
    return carregarImg(arquivo);
  }

  function carregarImg(arquivo) {
    return new Promise(function (ok, falha) {
      var img = new Image();
      img.onload = function () { ok(img); URL.revokeObjectURL(img.src); };
      img.onerror = falha;
      img.src = URL.createObjectURL(arquivo);
    });
  }

  function reduzir(arquivo) {
    if (!/^image\/(jpeg|png|webp)$/.test(arquivo.type) || arquivo.size < 350 * 1024) return Promise.resolve(arquivo);
    return carregar(arquivo).then(function (img) {
      var w = img.width, h = img.height, escala = Math.min(1, LADO_MAX / Math.max(w, h));
      var canvas = document.createElement("canvas");
      canvas.width = Math.round(w * escala); canvas.height = Math.round(h * escala);
      var ctx = canvas.getContext("2d");
      if (arquivo.type === "image/png") { ctx.fillStyle = "#fff"; ctx.fillRect(0, 0, canvas.width, canvas.height); }
      ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
      return new Promise(function (ok) {
        canvas.toBlob(function (blob) {
          if (!blob || blob.size >= arquivo.size) return ok(arquivo);
          var nome = arquivo.name.replace(/\.[^.]+$/, "") + ".jpg";
          ok(new File([blob], nome, { type: "image/jpeg", lastModified: Date.now() }));
        }, "image/jpeg", QUALIDADE);
      });
    }).catch(function () { return arquivo; });
  }

  document.addEventListener("change", function (e) {
    var input = e.target;
    if (!(input instanceof HTMLInputElement) || input.type !== "file" || input.dataset.reduzido === "1") return;
    var arquivos = Array.prototype.slice.call(input.files || []);
    if (!arquivos.length || typeof DataTransfer === "undefined") return;
    var form = input.form;
    var previa = input.parentElement.querySelector(".previa-upload");
    if (!previa) { previa = document.createElement("div"); previa.className = "previa-upload"; input.insertAdjacentElement("afterend", previa); }
    previa.innerHTML = '<span class="muted">Preparando fotos…</span>';
    if (form) form.dataset.aguardando = "1";
    Promise.all(arquivos.map(reduzir)).then(function (novos) {
      var dt = new DataTransfer();
      novos.forEach(function (f) { dt.items.add(f); });
      input.dataset.reduzido = "1";
      input.files = dt.files;
      input.dataset.reduzido = "";
      previa.innerHTML = "";
      novos.forEach(function (f, i) {
        var fig = document.createElement("figure");
        var antes = arquivos[i].size, depois = f.size;
        var texto = antes > depois ? formatar(antes) + " → " + formatar(depois) : formatar(depois);
        if (/^image\//.test(f.type)) {
          var img = document.createElement("img");
          img.src = URL.createObjectURL(f);
          fig.appendChild(img);
        } else {
          fig.innerHTML = '<div style="height:72px;display:grid;place-items:center;border:1px solid var(--border);border-radius:6px">PDF</div>';
        }
        var cap = document.createElement("figcaption");
        cap.textContent = texto;
        fig.appendChild(cap);
        previa.appendChild(fig);
      });
    }).finally(function () { if (form) form.dataset.aguardando = ""; });
  });
})();
