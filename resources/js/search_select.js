export default {
  init() {
    const wrappers = document.querySelectorAll('.search-select-wrapper');
    wrappers.forEach((wrapper) => this.enhance(wrapper));
  },

  enhance(wrapper) {
    const select = wrapper.querySelector('select.search-select');
    const ui = wrapper.querySelector('.select-ui-wrapper');
    const input = ui?.querySelector('input.search-input');
    const options = Array.from(ui?.querySelectorAll('.option-item') || []);

    if (!select || !ui || !input || options.length === 0) return;

    // Initialize input to current selection text
    const selected = select.options[select.selectedIndex];
    const selectedValue = selected?.value ?? '';
    const selectedItem = options.find(o => o.dataset.value === selectedValue);
    input.value = selectedItem?.querySelector('.main-text')?.textContent?.trim() || selected?.text || '';
    if (selectedItem) {
      selectedItem.classList.add('active');
    }

    // Open/close behavior
    const open = () => ui.classList.add('open');
    const close = () => ui.classList.remove('open');

    input.addEventListener('focus', open);
    input.addEventListener('click', (e) => {
      // Always open on click to avoid focus+click toggle close on first click
      open();
      e.stopPropagation();
    });

    document.addEventListener('click', (e) => {
      if (!ui.contains(e.target)) close();
    });

    // Filtering behavior
    input.addEventListener('input', () => {
      const q = input.value.trim().toLowerCase();
      options.forEach((opt) => {
        const text = opt.querySelector('.main-text')?.textContent?.toLowerCase() ?? '';
        const val = (opt.dataset.value || '').toLowerCase();
        const match = !q || text.includes(q) || val.includes(q);
        opt.classList.toggle('is-hidden', !match);
      });
    });

    // Keyboard select: Enter selects exact match if available, else first visible
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        const q = input.value.trim().toLowerCase();

        // Try exact match on value or main-text
        let target = options.find((opt) => {
          const text = opt.querySelector('.main-text')?.textContent?.trim().toLowerCase();
          const val = (opt.dataset.value || '').toLowerCase();
          return q && (text === q || val === q);
        });

        if (!target) {
          target = options.find((opt) => !opt.classList.contains('is-hidden')) || options[0];
        }

        if (target) this.selectOption(select, input, target);
        close();
      }
      if (e.key === 'Escape') {
        close();
      }
    });

    // Click select
    options.forEach((opt) => {
      opt.addEventListener('click', (e) => {
        e.preventDefault();
        this.selectOption(select, input, opt);
        close();
      });
    });
  },

  selectOption(select, input, optionEl) {
    const value = optionEl.dataset.value;
    const label = optionEl.querySelector('.main-text')?.textContent?.trim() || value;
    // Update native select
    select.value = value;
    // Update UI input
    input.value = label;
    // Mark active
    optionEl.parentElement.querySelectorAll('.option-item').forEach((o) => o.classList.remove('active'));
    optionEl.classList.add('active');

    // Tigger change event on native select
    select.dispatchEvent(new Event('change'));
  },
};
