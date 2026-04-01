document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.js-otp-form');
  if (!form) return;

  const boxes = Array.from(form.querySelectorAll('.otp-box'));
  const hiddenInput = form.querySelector('#otp_code');

  function updateHiddenInput() {
    hiddenInput.value = boxes.map(box => box.value).join('');
  }

  boxes.forEach((box, index) => {
    box.addEventListener('input', function () {
      this.value = this.value.replace(/\D/g, '').slice(0, 1);
      updateHiddenInput();

      if (this.value && index < boxes.length - 1) {
        boxes[index + 1].focus();
      }
    });

    box.addEventListener('keydown', function (e) {
      if (e.key === 'Backspace' && !this.value && index > 0) {
        boxes[index - 1].focus();
      }

      if (e.key === 'ArrowLeft' && index > 0) {
        boxes[index - 1].focus();
      }

      if (e.key === 'ArrowRight' && index < boxes.length - 1) {
        boxes[index + 1].focus();
      }
    });

    box.addEventListener('paste', function (e) {
      e.preventDefault();

      const pasted = (e.clipboardData || window.clipboardData)
        .getData('text')
        .replace(/\D/g, '')
        .slice(0, 6);

      if (!pasted) return;

      pasted.split('').forEach((char, i) => {
        if (boxes[i]) boxes[i].value = char;
      });

      updateHiddenInput();
      boxes[Math.min(pasted.length, boxes.length - 1)].focus();
    });
  });

  form.addEventListener('submit', (e) => {
    updateHiddenInput();

    if (hiddenInput.value.length !== 6) {
      e.preventDefault();
      alert('Inserisci tutte e 6 le cifre del codice.');
    }
  });

  boxes[0]?.focus();
});