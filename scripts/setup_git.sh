#!/bin/bash
# ════════════════════════════════════════════════════════════════
#  UNI-VIRTUAL — setup_git.sh
#  Script para inicializar el repositorio Git desde cero.
#  Ejecutar UNA sola vez desde la carpeta raíz del proyecto.
#
#  Uso: bash setup_git.sh [url_del_repositorio_remoto]
#  Ej:  bash setup_git.sh https://github.com/uniajc/univirtual.git
# ════════════════════════════════════════════════════════════════

set -e  # Detener si cualquier comando falla

REPO_URL="${1:-}"
VERSION="v8.0.0"
SPRINT="Sprint 5: bugs críticos resueltos, 4 roles, Jitsi Meet"

echo ""
echo "╔══════════════════════════════════════════════════╗"
echo "║       UNI-VIRTUAL — Inicializar Git              ║"
echo "╚══════════════════════════════════════════════════╝"
echo ""

# ── 1. Verificar que estamos en la carpeta correcta ──────
if [ ! -f "config/config.example.php" ]; then
  echo "❌ Error: ejecutar desde la raíz del proyecto (donde está config/)."
  exit 1
fi

# ── 2. Inicializar repositorio ────────────────────────────
if [ -d ".git" ]; then
  echo "⚠️  El repositorio Git ya existe. Saltando git init."
else
  git init
  echo "✅ Repositorio inicializado."
fi

# ── 3. Configurar usuario (si no está configurado) ────────
if [ -z "$(git config user.email)" ]; then
  git config user.email "edwinacarabali@gmail.com"
  git config user.name  "Edwin Carabali"
  echo "✅ Usuario Git configurado."
fi

# ── 4. Crear carpeta uploads con .gitkeep ─────────────────
mkdir -p uploads
touch uploads/.gitkeep
echo "✅ Carpeta uploads/ creada con .gitkeep."

# ── 5. Crear carpeta database (SQLite desarrollo) ─────────
mkdir -p database
touch database/.gitkeep
echo "✅ Carpeta database/ creada."

# ── 6. Agregar todos los archivos (respeta .gitignore) ────
git add .
echo "✅ Archivos agregados al stage."

# ── 7. Verificar que config.php NO está en stage ──────────
if git ls-files --cached | grep -q "config/config.php"; then
  echo "❌ ERROR: config/config.php está en stage. Verificar .gitignore."
  git rm --cached config/config.php
  echo "⚠️  config/config.php removido del stage."
fi

# ── 8. Primer commit ──────────────────────────────────────
git commit -m "chore: initial commit ${VERSION} — ${SPRINT}"
echo "✅ Primer commit realizado."

# ── 9. Crear rama main y develop ─────────────────────────
git branch -M main
git checkout -b develop
git checkout main
echo "✅ Ramas main y develop creadas."

# ── 10. Tag de versión ────────────────────────────────────
git tag -a "${VERSION}" -m "${SPRINT}"
echo "✅ Tag ${VERSION} creado."

# ── 11. Configurar remoto (opcional) ─────────────────────
if [ -n "${REPO_URL}" ]; then
  git remote add origin "${REPO_URL}"
  git push -u origin main
  git push -u origin develop
  git push origin --tags
  echo "✅ Código subido a ${REPO_URL}"
else
  echo ""
  echo "ℹ️  Repositorio local listo. Para conectar con GitHub:"
  echo "   git remote add origin https://github.com/uniajc/univirtual.git"
  echo "   git push -u origin main"
  echo "   git push -u origin develop"
  echo "   git push origin --tags"
fi

echo ""
echo "╔══════════════════════════════════════════════════╗"
echo "║  ✅ Git inicializado correctamente               ║"
echo "║                                                  ║"
echo "║  Rama activa:  main                              ║"
echo "║  Tag creado:   ${VERSION}                        ║"
echo "║  Próximo paso: checkout develop para trabajar    ║"
echo "╚══════════════════════════════════════════════════╝"
echo ""
echo "Flujo de trabajo para cada issue:"
echo "  git checkout develop"
echo "  git checkout -b feature/SCRUM-NNN-descripcion"
echo "  # ... hacer cambios ..."
echo "  git commit -m 'feat(SCRUM-NNN): descripción'"
echo "  git checkout develop && git merge feature/SCRUM-NNN-descripcion"
