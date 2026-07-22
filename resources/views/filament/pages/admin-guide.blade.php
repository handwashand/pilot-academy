<x-filament-panels::page>
    <style>
        .pa-guide { max-width: 62rem; line-height: 1.7; }
        .pa-guide h1 { font-size: 1.6rem; font-weight: 700; margin: 0 0 .5rem; }
        .pa-guide h2 { font-size: 1.25rem; font-weight: 700; margin: 2rem 0 .6rem; padding-top: .5rem; border-top: 1px solid rgb(128 128 128 / .18); }
        .pa-guide h3 { font-size: 1.05rem; font-weight: 600; margin: 1.3rem 0 .4rem; }
        .pa-guide p { margin: .5rem 0; }
        .pa-guide ul, .pa-guide ol { margin: .5rem 0 .5rem 1.4rem; }
        .pa-guide ul { list-style: disc; }
        .pa-guide ol { list-style: decimal; }
        .pa-guide li { margin: .25rem 0; }
        .pa-guide strong { font-weight: 700; }
        .pa-guide em { font-style: italic; }
        .pa-guide a { color: #2563eb; text-decoration: underline; }
        .pa-guide code { font-family: ui-monospace, Menlo, Consolas, monospace; font-size: .85em; background: rgb(128 128 128 / .14); padding: .1em .4em; border-radius: .3rem; }
        .pa-guide blockquote { margin: .8rem 0; padding: .6rem .9rem; border-left: 3px solid #3b82f6; background: rgb(59 130 246 / .08); border-radius: .3rem; }
        .pa-guide blockquote p { margin: 0; }
        .pa-guide table { border-collapse: collapse; width: 100%; margin: .8rem 0; font-size: .92em; display: block; overflow-x: auto; }
        .pa-guide th, .pa-guide td { border: 1px solid rgb(128 128 128 / .28); padding: .5rem .7rem; text-align: left; vertical-align: top; }
        .pa-guide th { background: rgb(128 128 128 / .1); font-weight: 600; white-space: nowrap; }
        .pa-guide hr { border: 0; border-top: 1px solid rgb(128 128 128 / .2); margin: 1.6rem 0; }
    </style>

    <div class="pa-guide">
        {!! $this->getGuideHtml() !!}
    </div>
</x-filament-panels::page>
