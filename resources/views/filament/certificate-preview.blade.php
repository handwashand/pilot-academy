{{-- Settings → Certificate: the certificate itself, drawn from the PDF's own
     view at A4 landscape (1123×794 CSS px) and scaled to the column's width.
     The HTML is rendered by IssueCertificate::previewHtml; srcdoc keeps it in
     its own document, so the PDF's styles never touch the panel. Inline
     styles: they work without a theme rebuild. --}}
<div
    x-data="{ scale: 1 }"
    x-init="const fit = () => { scale = $el.clientWidth / 1123 }; fit(); new ResizeObserver(fit).observe($el)"
    style="position: relative; width: 100%; aspect-ratio: 297 / 210; overflow: hidden; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,.15); background: #fbfaf6"
>
    <iframe
        title="{{ __t('admin_settings.certificate.preview') }}"
        srcdoc="{{ $html }}"
        sandbox=""
        :style="`transform: scale(${scale}); transform-origin: 0 0`"
        style="position: absolute; top: 0; left: 0; width: 1123px; height: 794px; border: 0; pointer-events: none"
    ></iframe>
</div>
