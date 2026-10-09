document.addEventListener('DOMContentLoaded', () => {
  const debugImages = document.querySelectorAll('img.debug-image');

  debugImages.forEach((img) => {
    // 1. Determine the "visible image object":
    // - if a link surrounds it: the whole <a>
    // - otherwise, if a .ratio surrounds it: that element
    // - otherwise: the <img> itself
    let baseElement = img;
    const linkParent = img.closest('a');
    const ratioParent = img.closest('.ratio');

    if (linkParent) {
      baseElement = linkParent;
    } else if (ratioParent) {
      baseElement = ratioParent;
    }

    // 2. Build a wrapper around this baseElement
    const wrapper = document.createElement('div');
    wrapper.classList.add('debug-image-wrapper');
    wrapper.style.position = 'relative';
    wrapper.style.display = 'inline-block';
    wrapper.style.width = '100%';

    // Move baseElement into the wrapper
    baseElement.parentNode.insertBefore(wrapper, baseElement);
    wrapper.appendChild(baseElement);

    // 3. Create the overlay and insert it into the wrapper
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
          `Rendered width: ${renderedWidth}px<br>` +
          `devicePixelRatio: ${dpr}<br>` +
          `Required physical width: ${requiredPhysicalWidth}px<br>` +
          `srcset image used: ${currentSrc.split('/').pop()}<br>` +
          `Natural size: ${tmpImg.naturalWidth}×${tmpImg.naturalHeight}px`;
      };
    };


    updateDebugInfo();
    window.addEventListener('resize', updateDebugInfo);
  });
});
