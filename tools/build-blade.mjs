#!/usr/bin/env node
/* Builds the Laravel Blade views in blade/ from the HTML templates in
   overlays/ and stories/, so the two never drift apart.

     node tools/build-blade.mjs

   Each view is self-contained (shared CSS and JS inlined) and reads every
   field from one $aa array, defined in tools/blade/*-data.blade.php. To point
   the views at different model fields, edit those files and rebuild. */
import { readFileSync, writeFileSync, readdirSync, mkdirSync } from "node:fs";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";

const ROOT = join(dirname(fileURLToPath(import.meta.url)), "..");
const read = (p) => readFileSync(join(ROOT, p), "utf8");

const KINDS = {
  overlays: {
    out: "blade/youtube",
    prefix: "overlay",
    css: ["shared/base.css", "shared/overlay.css"],
    data: "tools/blade/overlay-data.blade.php",
    scripts: ["tools/blade/stage-fit.js", "tools/blade/overlay-live.js"],
    vite: true
  },
  stories: {
    out: "blade/player-card",
    prefix: "story",
    css: ["shared/base.css", "shared/story.css"],
    data: "tools/blade/story-data.blade.php",
    scripts: ["tools/blade/stage-fit.js", "shared/story.js"],
    vite: false
  }
};

const indent = (s, n) => s.replace(/^(?=.)/gm, " ".repeat(n));
const between = (s, a, b, file) => {
  const i = s.indexOf(a), j = s.indexOf(b, i + a.length);
  if (i < 0 || j < 0) throw new Error(`${file}: cannot find ${a} ... ${b}`);
  return s.slice(i + a.length, j);
};

function sharedCss(files) {
  return files.map((f) => read(f)
    // The ?preview camera backdrop is for the gallery only.
    .split("\n").filter((l) => !l.includes("aa-preview")).join("\n")
    .trim()).join("\n\n");
}

function convertMarkup(html, file) {
  const binds = (html.match(/data-bind="/g) || []).length;
  let filled = 0;
  html = html.replace(/(<(\w+)\b[^>]*\sdata-bind="([^"]+)"[^>]*>)(<\/\2>)/g, (_, open, tag, key, close) => {
    filled++;
    return `${open}{{ $aa['${key}'] }}${close}`;
  });
  if (filled !== binds) throw new Error(`${file}: ${binds - filled} data-bind element(s) are not empty leaf tags`);
  html = html.replace(/\sdata-src="([^"]+)"/g, (m, key) => `${m} src="{{ $aa['${key}'] }}"`);
  html = html.replace(/\sdata-bg="([^"]+)"/g, (m, key) => `${m} style="background-image: url('{{ $aa['${key}'] }}')"`);
  if (/\.\.\/(assets|shared)\//.test(html)) throw new Error(`${file}: markup still points at ../assets or ../shared`);
  return html;
}

function build(kind, file) {
  const k = KINDS[kind];
  const src = read(`${kind}/${file}`);
  const title = between(src, "<title>", "</title>", file);
  const fonts = (src.match(/<link [^>]*fonts\.(googleapis|gstatic)[^>]*>/g) || []).join("\n");
  const css = between(src, "<style>", "</style>", file).trim();
  const stageOpen = src.match(/<div class="aa-stage"[^>]*>/)[0];
  const body = between(src, stageOpen, "\n</div>\n<script", file);
  if (/\{\{|@(?:if|php|foreach|section|include)\b/.test(css + body)) throw new Error(`${file}: template contains Blade syntax`);

  const scripts = k.scripts.map((s) => `    <script>\n${indent(read(s).trim(), 8)}\n    </script>`).join("\n");
  return `{{-- ${title}
     Generated from ${kind}/${file} by tools/build-blade.mjs. Do not edit by hand:
     change the template or tools/blade/*, then run: node tools/build-blade.mjs --}}
@extends('frontend.layout.blank-layout')

${read(k.data).trim()}

@section('styles')
${indent(fonts, 4)}
    <style>
${indent(sharedCss(k.css), 8)}

${indent(css, 8)}

        :root { --team: {{ $aa['team.color'] }}; }
    </style>
@endsection

@section('content')
    ${stageOpen}
${indent(convertMarkup(body, file).replace(/^\n+/, ""), 4)}
    </div>
@endsection

@section('page-scripts')
${k.vite ? "    @vite('resources/js/app.js')\n" : ""}${scripts}
@endsection
`;
}

for (const kind of Object.keys(KINDS)) {
  const k = KINDS[kind];
  mkdirSync(join(ROOT, k.out), { recursive: true });
  for (const file of readdirSync(join(ROOT, kind)).filter((f) => f.endsWith(".html")).sort()) {
    const out = `${k.out}/${k.prefix}-${file.replace(/\.html$/, ".blade.php")}`;
    writeFileSync(join(ROOT, out), build(kind, file));
    console.log("wrote", out);
  }
}
