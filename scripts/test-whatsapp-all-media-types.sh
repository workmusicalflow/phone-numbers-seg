#!/bin/bash

# Test de tous les types de médias WhatsApp

echo "Test de tous les types de médias WhatsApp"
echo "========================================"

# Fonction pour se connecter
login() {
    echo "Connexion..."
    curl -s -c cookies.txt -X POST http://localhost:8000/graphql.php \
      -H "Content-Type: application/json" \
      -H "Origin: http://localhost:5173" \
      -d '{
        "query": "mutation { login(username: \"admin\", password: \"admin123\") }"
      }' > /dev/null
}

# Fonction pour tester l'upload
test_upload() {
    local FILE_PATH="$1"
    local FILE_TYPE="$2"
    local DESCRIPTION="$3"
    
    echo -e "\n## Test: $DESCRIPTION"
    echo "Fichier: $FILE_PATH"
    echo "Type MIME: $FILE_TYPE"
    
    RESPONSE=$(curl -s -b cookies.txt -X POST http://localhost:8000/api/whatsapp/upload.php \
      -H "Origin: http://localhost:5173" \
      -H "Accept: application/json" \
      -F "file=@$FILE_PATH;type=$FILE_TYPE")
    
    echo "Réponse: $RESPONSE"
    
    # Vérifier le succès
    if echo "$RESPONSE" | grep -q '"success":true'; then
        echo "✅ Upload réussi"
    else
        echo "❌ Upload échoué"
    fi
}

# Se connecter
login

# 1. Test image JPEG
echo -e "\n### 1. Image JPEG"
JPEG_PATH="/tmp/test_image.jpg"
echo "/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSopGR8tMC0oMCUoKSj/2wBDAQcHBwoIChMKChMoGhYaKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCj/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAr/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=" | base64 -d > "$JPEG_PATH"
test_upload "$JPEG_PATH" "image/jpeg" "Image JPEG"

# 2. Test PDF
echo -e "\n### 2. Document PDF"
PDF_PATH="/tmp/test_document.pdf"
echo "%PDF-1.4
1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj
2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj
3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]/Resources<<>>>>endobj
xref
0 4
0000000000 65535 f
0000000009 00000 n
0000000058 00000 n
0000000115 00000 n
trailer<</Size 4/Root 1 0 R>>
startxref
203
%%EOF" > "$PDF_PATH"
test_upload "$PDF_PATH" "application/pdf" "Document PDF"

# 3. Test audio MP3 (simulé)
echo -e "\n### 3. Audio MP3"
MP3_PATH="/tmp/test_audio.mp3"
# Créer un fichier MP3 minimal
echo -e "\xFF\xFB\x90\x00" > "$MP3_PATH"
test_upload "$MP3_PATH" "audio/mpeg" "Audio MP3"

# 4. Test vidéo MP4 (simulé)
echo -e "\n### 4. Vidéo MP4"
MP4_PATH="/tmp/test_video.mp4"
# Créer un fichier MP4 minimal
echo -e "\x00\x00\x00\x20\x66\x74\x79\x70\x69\x73\x6F\x6D" > "$MP4_PATH"
test_upload "$MP4_PATH" "video/mp4" "Vidéo MP4"

# 5. Test document Word
echo -e "\n### 5. Document Word"
DOCX_PATH="/tmp/test_document.docx"
# Créer un fichier DOCX minimal (zip vide avec structure)
echo "UEsFBgAAAAAAAAAAAAAAAAAAAAAAAA==" | base64 -d > "$DOCX_PATH"
test_upload "$DOCX_PATH" "application/vnd.openxmlformats-officedocument.wordprocessingml.document" "Document Word"

# Nettoyer
rm -f cookies.txt "$JPEG_PATH" "$PDF_PATH" "$MP3_PATH" "$MP4_PATH" "$DOCX_PATH"

echo -e "\n### Test terminé"