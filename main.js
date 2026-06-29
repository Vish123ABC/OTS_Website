// main.js - interactions for OTS preview full site
document.addEventListener('DOMContentLoaded', ()=>{

  // Slider
  const slides = document.querySelectorAll('.slide');
  let idx = 0;
  function show(i){ slides.forEach((s,si)=> s.classList.toggle('active', si===i)); }
  show(0);
  document.getElementById('next')?.addEventListener('click', ()=>{ idx=(idx+1)%slides.length; show(idx); });
  document.getElementById('prev')?.addEventListener('click', ()=>{ idx=(idx-1+slides.length)%slides.length; show(idx); });
  setInterval(()=>{ idx=(idx+1)%slides.length; show(idx); }, 6000);

  // Dropdown click toggle (for mobile & click)
  document.querySelectorAll('.dropdown-toggle').forEach(btn=>{
    btn.addEventListener('click', (e)=>{
      e.preventDefault();
      const dropdown = btn.closest('.dropdown');
      dropdown.classList.toggle('open');
    });
  });

  // Mobile nav toggle
  const navToggle = document.getElementById('navToggle');
  const navList = document.getElementById('navList');
  navToggle?.addEventListener('click', ()=>{ navList.classList.toggle('show'); });

  // simple lightbox for gallery images (delegated)
  document.querySelectorAll('.gallery-grid img').forEach(img=>{
    img.addEventListener('click', ()=>{
      const src = img.getAttribute('src');
      const light = document.createElement('div');
      light.style.position='fixed'; light.style.inset=0; light.style.background='rgba(0,0,0,0.85)'; light.style.display='flex';
      light.style.alignItems='center'; light.style.justifyContent='center'; light.style.zIndex=9999;
      const im = document.createElement('img'); im.src=src; im.style.maxWidth='90%'; im.style.maxHeight='90%'; im.style.borderRadius='8px';
      light.appendChild(im);
      light.addEventListener('click', ()=> document.body.removeChild(light));
      document.body.appendChild(light);
    });
  });

});
