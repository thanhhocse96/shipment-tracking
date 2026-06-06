#!/bin/bash
# collect_vault.sh — Thu thập facts về Obsidian vault
# Chạy: bash collect_vault.sh /path/to/vault > vault_facts.txt
# Sau đó paste vault_facts.txt vào init prompt

VAULT="${1:-.}"

echo "=== VAULT PATH ==="
realpath "$VAULT"
echo ""

echo "=== FOLDER STRUCTURE (2 levels) ==="
find "$VAULT" -maxdepth 2 -not -path '*/.obsidian*' -not -path '*/.trash*' | \
  sort | \
  sed "s|$VAULT/||" | head -60
echo ""

echo "=== NOTE COUNT PER FOLDER ==="
find "$VAULT" -name "*.md" -not -path '*/.obsidian*' | \
  awk -F/ '{
    if (NF==2) print "  (root)/"$2
    else print "  "$NF" in "$NF-1"/"
  }' | \
  sort | uniq -c | sort -rn | head -20
echo ""

echo "=== TOTAL NOTES ==="
find "$VAULT" -name "*.md" -not -path '*/.obsidian*' | wc -l
echo ""

echo "=== FIRST LINE OF EACH NOTE (for naming convention) ==="
find "$VAULT" -name "*.md" -not -path '*/.obsidian*' | sort | head -20 | \
  while read f; do
    title=$(head -3 "$f" | grep -m1 "^#" | sed 's/^#*//' | xargs)
    fname=$(basename "$f")
    echo "  FILE: $fname"
    [ -n "$title" ] && echo "  H1:   $title"
  done
echo ""

echo "=== FRONTMATTER SAMPLE (first 5 notes with frontmatter) ==="
count=0
find "$VAULT" -name "*.md" -not -path '*/.obsidian*' | sort | while read f; do
  if head -1 "$f" | grep -q "^---"; then
    echo "--- $(basename "$f") ---"
    head -10 "$f"
    echo ""
    count=$((count+1))
    [ $count -ge 5 ] && break
  fi
done
echo ""

echo "=== INTERNAL LINKS DENSITY (top 10 most-linked notes) ==="
find "$VAULT" -name "*.md" -not -path '*/.obsidian*' -exec grep -ho '\[\[[^]]*\]\]' {} \; | \
  sort | uniq -c | sort -rn | head -10
echo ""

echo "=== TAGS FOUND ==="
find "$VAULT" -name "*.md" -not -path '*/.obsidian*' -exec grep -ho '#[a-zA-Z][a-zA-Z0-9/_-]*' {} \; | \
  sort | uniq -c | sort -rn | head -20
echo ""

echo "=== DONE ==="
