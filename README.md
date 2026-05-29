# Cota Bem — Landing Page

Landing page do **Cota Bem**: consórcio com método e planejamento (imobiliário, veículos, frota e pesados), com análise consultiva, planejamento de lance e transparência em cada etapa.

## Estrutura

```
.
├── index.html      # Página principal (HTML + CSS/JS inline)
├── assets/         # Imagens e logo
└── icons/          # Ícones SVG das seções
```

## Como rodar localmente

É um site estático, sem build. Basta abrir o `index.html` no navegador, ou servir a pasta:

```bash
python3 -m http.server 8000
# acesse http://localhost:8000
```

## Deploy

Por ser estático, pode ser hospedado em GitHub Pages, Netlify, Vercel ou qualquer servidor de arquivos.
