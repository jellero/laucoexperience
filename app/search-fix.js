'use strict';

document.addEventListener('input', event => {
  if (event.target.id !== 'route-search') return;
  state.query = event.target.value;
  window.clearTimeout(window.laucoSearchTimer);
  window.laucoSearchTimer = window.setTimeout(() => {
    view.innerHTML = exploreView();
    const next = document.querySelector('#route-search');
    if (next) {
      next.focus({preventScroll:true});
      next.setSelectionRange(next.value.length,next.value.length);
    }
  },120);
});
