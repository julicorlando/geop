FROM python:3.13-slim

ENV PYTHONDONTWRITEBYTECODE=1 PYTHONUNBUFFERED=1 PIP_NO_CACHE_DIR=1
WORKDIR /app

# libwebp/libjpeg já vêm nas wheels do Pillow; nada extra a instalar.
COPY requirements.txt .
RUN pip install -r requirements.txt

COPY . .
RUN DJANGO_SECRET_KEY=build python manage.py collectstatic --noinput \
    && useradd --create-home geop && mkdir -p /data/media && chown -R geop /data /app
USER geop

ENV MEDIA_ROOT=/data/media
VOLUME ["/data/media"]
EXPOSE 8000
CMD ["sh", "-c", "python manage.py migrate --noinput && gunicorn config.wsgi --bind 0.0.0.0:8000 --workers ${WEB_CONCURRENCY:-3} --timeout 120 --access-logfile -"]
