document.addEventListener('DOMContentLoaded', () => {
  const debugImages = document.querySelectorAll('img.debug-image');

  debugImages.forEach((img) => {
    // 1. Bestimme das „sichtbare Bildobjekt“:
    // - Wenn ein Link drum herum: komplettes <a>
    // - sonst: wenn eine .ratio drum herum: diese
    // - sonst: direkt das <img>
    let baseElement = img;
    const linkParent = img.closest('a');
    const ratioParent = img.closest('.ratio');

    if (linkParent) {
      baseElement = linkParent;
    } else if (ratioParent) {
      baseElement = ratioParent;
    }

    // 2. Wrapper um dieses baseElement bauen
    const wrapper = document.createElement('div');
    wrapper.classList.add('debug-image-wrapper');
    wrapper.style.position = 'relative';
    wrapper.style.display = 'inline-block';
    wrapper.style.width = '100%';

    // baseElement in Wrapper verschieben
    baseElement.parentNode.insertBefore(wrapper, baseElement);
    wrapper.appendChild(baseElement);

    // 3. Overlay erzeugen und in Wrapper einfügen
    const overlay = document.createElement('div');
    overlay.classList.add('debug-overlay');
    overlay.style.position = 'absolute';
    overlay.style.left = 0;
    overlay.style.right = 0;
    overlay.style.bottom = 0;
    overlay.style.background = 'rgba(0, 0, 0, 0.7)';
    overlay.style.color = '#fff';
    overlay.style.fontSize = '12px';
    overlay.style.fontFamily = 'monospace';
    overlay.style.padding = '4px 6px';
    overlay.style.pointerEvents = 'none';
    overlay.style.zIndex = '20';

    wrapper.appendChild(overlay);

    const updateDebugInfo = () => {
      const renderedWidth = img.clientWidth;
      const dpr = window.devicePixelRatio || 1;
      const currentSrc = img.currentSrc || img.src;
      const requiredPhysicalWidth = Math.round(renderedWidth * dpr);

      const tmpImg = new Image();
      tmpImg.src = currentSrc;
      tmpImg.onload = () => {
        overlay.innerHTML =
          `Gerenderte Breite: ${renderedWidth}px<br>` +
          `devicePixelRatio: ${dpr}<br>` +
          `Benötigte physische Breite: ${requiredPhysicalWidth}px<br>` +
          `Verwendetes srcset-Bild: ${currentSrc.split('/').pop()}<br>` +
          `Echte Größe: ${tmpImg.naturalWidth}×${tmpImg.naturalHeight}px`;
      };
    };


    updateDebugInfo();
    window.addEventListener('resize', updateDebugInfo);
  });
});
