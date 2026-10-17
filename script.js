const select = (selector, parent = document) => parent.querySelector(selector);

select('#year').textContent = new Date().getFullYear();

const menuButton = select('.menu-toggle');
const navLinks = select('.nav-links');
menuButton.addEventListener('click', () => {
  const open = navLinks.classList.toggle('open');
  menuButton.setAttribute('aria-expanded', open);
  menuButton.innerHTML = `<i class="fa-solid fa-${open ? 'xmark' : 'bars'}"></i>`;
});
navLinks.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
  navLinks.classList.remove('open');
  menuButton.setAttribute('aria-expanded', 'false');
  menuButton.innerHTML = '<i class="fa-solid fa-bars"></i>';
}));

const observer = new IntersectionObserver((entries) => entries.forEach((entry) => {
  if (entry.isIntersecting) { entry.target.classList.add('active'); observer.unobserve(entry.target); }
}), { threshold: 0.12 });
document.querySelectorAll('.reveal').forEach((element) => observer.observe(element));

const scrollTop = select('.scroll-top');
window.addEventListener('scroll', () => scrollTop.classList.toggle('visible', scrollY > 550), { passive: true });
scrollTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

select('#contact-form').addEventListener('submit', (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const name = form.elements.name.value.trim();
  const device = form.elements.device.value;
  const message = form.elements.message.value.trim() || 'I would like to know more.';
  select('.form-status', form).textContent = `Thanks, ${name}. Your inquiry about ${device} has been received.`;
  form.reset();
});
