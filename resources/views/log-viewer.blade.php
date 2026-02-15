<x-filament-panels::page>
<div>
<style>
.log-scroller::-webkit-scrollbar{width:10px;height:10px}
.log-scroller::-webkit-scrollbar-track{background:#161b22}
.log-scroller::-webkit-scrollbar-thumb{background:#30363d;border-radius:5px}
.log-scroller::-webkit-scrollbar-thumb:hover{background:#484f58}
.log-scroller::-webkit-scrollbar-corner{background:#161b22}
.log-scroller{scrollbar-color:#30363d #161b22;scrollbar-width:thin}
#log-search:focus{outline:none!important;box-shadow:none!important;border-color:transparent!important}
.log-loading{display:flex;align-items:center;justify-content:center;gap:8px;padding:48px;color:#6b7280}
.log-spinner{width:20px;height:20px;border:2px solid #30363d;border-top-color:#58a6ff;border-radius:50%;animation:spin .8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
</style>
<script src="/plugins/log-viewer/log-viewer.js"></script>
<div class="mb-4">
<form wire:submit.prevent="">{{ $this->form }}</form>
</div>
<div class="mb-3 flex gap-2">
</div>
@if($selectedFile)
<div wire:ignore class="w-full" x-data="logViewer()">
<div class="mb-3 flex flex-wrap items-center gap-2">
<span class="text-xs text-gray-500" x-text="filtered.length + ' / ' + allLines.length"></span>
<span class="text-gray-700 mx-1">|</span>
<button @click="toggleLevel('error')" :style="btnStyle('error','239,68,68','#f87171')" class="px-3 py-1.5 rounded-md text-xs font-medium transition-all cursor-pointer hover:opacity-90">Error</button>
<button @click="toggleLevel('warn')" :style="btnStyle('warn','245,158,11','#fbbf24')" class="px-3 py-1.5 rounded-md text-xs font-medium transition-all cursor-pointer hover:opacity-90">Warn</button>
<button @click="toggleLevel('info')" :style="btnStyle('info','14,165,233','#38bdf8')" class="px-3 py-1.5 rounded-md text-xs font-medium transition-all cursor-pointer hover:opacity-90">Info</button>
<button @click="toggleLevel('debug')" :style="btnStyle('debug','139,92,246','#a78bfa')" class="px-3 py-1.5 rounded-md text-xs font-medium transition-all cursor-pointer hover:opacity-90">Debug</button>
<button @click="toggleLevel('default')" :style="btnStyle('default','107,114,128','#9ca3af')" class="px-3 py-1.5 rounded-md text-xs font-medium transition-all cursor-pointer hover:opacity-90">Other</button>
</div>
<div class="mb-4">
<input id="log-search" type="text" x-model.debounce.300ms="search" placeholder="Search: ERROR, WARN, player name..." style="background:rgba(255,255,255,0.05);border:none;outline:none;box-shadow:none" class="w-full rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500"/>
</div>
<div class="rounded-xl overflow-hidden bg-[#0d1117] w-full">
<div x-show="loading" class="log-loading"><div class="log-spinner"></div><span>Loading...</span></div>
<div x-show="!loading" x-ref="scroller" @scroll.passive="onScroll($event)" class="log-scroller" style="overflow:scroll;max-height:65vh;font-family:ui-monospace,SFMono-Regular,'SF Mono',Menlo,Consolas,monospace;font-size:13px;line-height:20px">
<div :style="'height:'+totalHeight+'px;position:relative;min-width:100%'">
<div :style="'position:absolute;top:'+offsetY+'px;left:0;right:0'">
<template x-for="line in visibleLines" :key="line.number">
<div class="flex" :style="'white-space:pre;height:20px;background:'+levelBg(line.level)" @mouseenter="$el.style.background='rgba(255,255,255,0.04)'" @mouseleave="$el.style.background=levelBg(line.level)"><span style="min-width:56px;width:56px;text-align:right;padding-right:12px;color:#2d333b;user-select:none;border-right:1px solid #1b2028;flex-shrink:0;position:sticky;left:0;background:#0d1117;z-index:1" x-text="line.number"></span><span style="padding-left:12px;padding-right:16px" :style="'color:'+levelColor(line.level)" x-html="highlight(line.text)"></span></div>
</template>
</div>
</div>
</div>
</div>
<div x-show="!loading && filtered.length===0" class="rounded-xl p-8 text-center text-gray-500 bg-[#0d1117] mt-2">No matching lines</div>
</div>
@else
<div class="rounded-xl p-8 text-center text-gray-500 bg-[#0d1117]">Select a log file to view</div>
@endif
</div>
</x-filament-panels::page>
