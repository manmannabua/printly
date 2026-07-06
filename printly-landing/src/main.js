import './styles.css'

const header = document.querySelector('.site-header')

if (header) {
  const setHeaderState = () => {
    header.toggleAttribute('data-scrolled', window.scrollY > 8)
  }

  setHeaderState()
  window.addEventListener('scroll', setHeaderState, { passive: true })
}
