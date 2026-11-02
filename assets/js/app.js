document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('.nav-toggle');
  if (toggle) toggle.addEventListener('click', () => document.getElementById('menu').classList.toggle('open'));
  document.querySelectorAll('[data-confirm]').forEach(b =>
    b.addEventListener('click', e => { if (!confirm(b.dataset.confirm)) e.preventDefault(); }));
  const photo = document.getElementById('photo'), prev = document.getElementById('preview');
  if (photo && prev) photo.addEventListener('change', () => {
    const f = photo.files[0];
    if (f && f.type.startsWith('image/')) { prev.src = URL.createObjectURL(f); prev.hidden = false; } else prev.hidden = true;
  });
});
