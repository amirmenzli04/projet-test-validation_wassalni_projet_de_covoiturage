/**
 * Convertit un document Markdown du dossier en PDF (rendu Chrome via Playwright). Sous-ensemble Markdown :
 * titres, paragraphes, listes, tableaux, images, code, gras/italique, liens, règles horizontales.
 * Usage : node scripts/generer-pdf.js docs/rapport-final/rapport-final.md docs/rapport-final/rapport-final.pdf
 */
const fs = require('node:fs');
const path = require('node:path');
const { pathToFileURL } = require('node:url');
const { chromium } = require('@playwright/test');

const [source, target] = process.argv.slice(2);
const sourceDir = path.dirname(path.resolve(source));
const esc = s => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
const inline = s => esc(s)
    .replace(/!\[([^\]]*)\]\(([^)]+)\)/g, (_, alt, src) => `<img alt="${alt}" src="${pathToFileURL(path.resolve(sourceDir, src)).href}">`)
    .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a>$1</a>')
    .replace(/`([^`]+)`/g, '<code>$1</code>')
    .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
    .replace(/(^|[\s(])\*([^*\s][^*]*)\*/g, '$1<em>$2</em>');

function render(md) {
    const lines = md.replace(/\r/g, '').split('\n');
    const out = [];
    for (let i = 0; i < lines.length; i++) {
        const line = lines[i];
        if (line.startsWith('```')) {
            const code = [];
            while (++i < lines.length && !lines[i].startsWith('```')) code.push(lines[i]);
            out.push(`<pre>${esc(code.join('\n'))}</pre>`);
        } else if (/^#{1,4} /.test(line)) {
            const level = line.match(/^#+/)[0].length;
            out.push(`<h${level}>${inline(line.slice(level + 1))}</h${level}>`);
        } else if (/^---+$/.test(line.trim())) {
            out.push('<hr>');
        } else if (line.startsWith('|')) {
            const rows = [];
            while (i < lines.length && lines[i].startsWith('|')) rows.push(lines[i++]);
            i--;
            const cells = r => r.trim().replace(/^\||\|$/g, '').split('|').map(c => c.trim());
            const body = rows.filter((_, k) => k !== 1).map((r, k) => `<tr>${cells(r).map(c => k === 0 ? `<th>${inline(c)}</th>` : `<td>${inline(c)}</td>`).join('')}</tr>`);
            out.push(`<table>${body.join('')}</table>`);
        } else if (/^\s*([-*]|\d+\.) /.test(line)) {
            const ordered = /^\s*\d+\./.test(line);
            const items = [];
            while (i < lines.length && /^\s*([-*]|\d+\.) /.test(lines[i])) items.push(lines[i++].replace(/^\s*([-*]|\d+\.) /, ''));
            i--;
            out.push(`<${ordered ? 'ol' : 'ul'}>${items.map(t => `<li>${inline(t)}</li>`).join('')}</${ordered ? 'ol' : 'ul'}>`);
        } else if (line.trim() === '') {
            continue;
        } else {
            const para = [line];
            while (i + 1 < lines.length && lines[i + 1].trim() !== '' && !/^(#|\||```|\s*([-*]|\d+\.) |---)/.test(lines[i + 1])) para.push(lines[++i]);
            out.push(`<p>${inline(para.join(' '))}</p>`);
        }
    }
    return out.join('\n');
}

(async () => {
    const html = `<!doctype html><html lang="fr"><head><meta charset="utf-8"><style>
body{font-family:"Segoe UI",Arial,sans-serif;font-size:10.5pt;line-height:1.45;color:#1f2937}
h1{color:#3730a3;font-size:20pt;border-bottom:3px solid #6366f1;padding-bottom:6px}
h2{color:#3730a3;font-size:14pt;margin-top:22px;border-bottom:1px solid #c7d2fe;page-break-after:avoid}
h3{font-size:12pt;color:#312e81}
table{border-collapse:collapse;width:100%;margin:8px 0;font-size:9pt;page-break-inside:avoid}
th,td{border:1px solid #cbd5e1;padding:4px 6px;vertical-align:top;text-align:left}th{background:#eef2ff}
img{max-width:100%;max-height:430px;display:block;margin:10px auto;border:1px solid #cbd5e1}
code{background:#f1f5f9;padding:0 3px;font-size:9pt}pre{background:#0f172a;color:#e2e8f0;padding:8px;font-size:8.5pt;white-space:pre-wrap}
p{margin:6px 0}a{color:#3730a3}hr{border:0;border-top:1px solid #cbd5e1}
</style></head><body>${render(fs.readFileSync(source, 'utf8'))}</body></html>`;
    const chrome = process.env.CHROME_PATH || 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
    const browser = await chromium.launch({ executablePath: fs.existsSync(chrome) ? chrome : undefined });
    const page = await browser.newPage();
    const tmp = path.join(path.dirname(path.resolve(target)), '.rendu-temporaire.html');
    fs.writeFileSync(tmp, html);
    await page.goto(pathToFileURL(tmp).href);
    await page.pdf({ path: target, format: 'A4', margin: { top: '16mm', bottom: '16mm', left: '14mm', right: '14mm' }, printBackground: true,
        displayHeaderFooter: true, headerTemplate: '<span></span>', footerTemplate: '<div style="font-size:8px;width:100%;text-align:center;color:#64748b">WASSALNI — dossier de test — page <span class="pageNumber"></span>/<span class="totalPages"></span></div>' });
    fs.unlinkSync(tmp);
    await browser.close();
    console.log('PDF :', target);
})();
