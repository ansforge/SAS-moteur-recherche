export default class ComboboxAccessibility {
  /** @type {HTMLInputElement?} */
  #comboboxNode;

  /** @type {HTMLLIElement[]} */
  #optionNodes;

  #currentOptionIndex;

  /** @type {import('vue').Ref<string>?} */
  #inputText;

  constructor() {
    this.#comboboxNode = null;

    this.#optionNodes = [];

    this.#currentOptionIndex = -1;

    this.#inputText = null;

    /** Tells wether or not the last keypress action was captured by this model */
    this.keyFlag = false;
  }

  listBoxIsEmpty = () => (this.#optionNodes.length === 0);

  onComboboxKeyDown = (event) => {
    this.keyFlag = false;

    if (event.ctrlKey || event.shiftKey || this.listBoxIsEmpty()) {
      return;
    }

    switch (event.key) {
      case 'Esc':
      case 'Escape':
        this.#comboboxNode.blur();
        this.keyFlag = true;
        break;

      case 'Enter':
        if (this.#optionNodes[this.#currentOptionIndex]) {
          this.#inputText.value = this.#optionNodes[this.#currentOptionIndex].textContent;
          this.keyFlag = true;
        }

        this.reset();
        break;

      case 'Down':
      case 'ArrowDown':
        this.shiftCurrentFocusedInput(+1);
        this.keyFlag = true;
        break;

      case 'Up':
      case 'ArrowUp':
        this.shiftCurrentFocusedInput(-1);
        this.keyFlag = true;
        break;

      default:
        break;
    }

    if (this.keyFlag) {
      event.stopPropagation();
      event.preventDefault();
    }
  };

    /**
     * @param {object} _
     * @param {HTMLInputElement} _.comboboxNode
     * @param {ref<String>} _.inputText
     */
    init = ({ comboboxNode, inputText }) => {
      this.#comboboxNode = comboboxNode;
      this.#inputText = inputText;

      this.#comboboxNode.addEventListener('keydown', this.onComboboxKeyDown);
    };

    dispose = () => {
      if (!this.#comboboxNode) return;
      this.#comboboxNode.removeEventListener('keydown', this.onComboboxKeyDown);
    };

    shiftCurrentFocusedInput = (offset) => {
      const indexMax = this.#optionNodes.length - 1;
      const lastOptionIndex = this.#currentOptionIndex;

      this.#currentOptionIndex += offset;
      if (this.#currentOptionIndex < 0) {
        this.#currentOptionIndex = indexMax;
      } else if (this.#currentOptionIndex > indexMax) {
        this.#currentOptionIndex = 0;
      }

      if (lastOptionIndex !== -1) {
        this.#optionNodes[lastOptionIndex].classList.remove('focus');
        this.#optionNodes[lastOptionIndex].removeAttribute('aria-selected');
      }
      this.#optionNodes[this.#currentOptionIndex].classList.add('focus');
      this.#optionNodes[this.#currentOptionIndex].setAttribute('aria-selected', 'true');
      this.#optionNodes[this.#currentOptionIndex].scrollIntoView(false);
    };

    /**
     * @param {HTMLCollection} listItems
     */
    update = (listItems) => {
      this.reset();
      if (!listItems) return;
      /* eslint-disable no-restricted-syntax */
      for (const li of listItems) {
        this.#optionNodes.push(li);
      }
    };

    reset = () => {
      this.#currentOptionIndex = -1;
      /* eslint-disable no-restricted-syntax */
      for (const node of this.#optionNodes) {
        node.classList.remove('focus');
      }
      this.#optionNodes.length = 0;
    };
}
