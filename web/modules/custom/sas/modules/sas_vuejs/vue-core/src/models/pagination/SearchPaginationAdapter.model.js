import PaginationAdapter from './PaginationAdapter.model';

/**
 * This adapter display a list between 6 and 7 buttons depending on
 * if the currentLotNumber is 1 or equal to the totalNumberOfLots
 */
export default class SearchPaginationAdapter extends PaginationAdapter {
  static buildButtons({
    currentLotNumber,
    totalNumberOfLots,
    actions,
    emit,
    eventName = 'go-to-lot',
  }) {
    const buttonPlaceholder = (content, disabled = false) => (
      `<button type="button" ${disabled ? 'disabled' : ''}>
          ${content}
        </button>
      `
    );

    const buildBackButtons = () => {
      const disabled = currentLotNumber === 1;
      return [
        {
          action: actions.goBackToFirstLot.bind(null, { emit, eventName }),
          placeholderClass: 'p-first-page',
          content: buttonPlaceholder(`
            <span class="sr-only">
              Retour à la première page
            </span>
            <i aria-hidden="true" class="sas-icon sas-icon-first"></i>
          `, disabled),
        },
        {
          action: actions.goBackToPreviousLot.bind(null, { emit, eventName }),
          placeholderClass: 'p-previous-page',
          content: buttonPlaceholder(`
            <span>
              <span>
                Page précédente
              </span>
              <i aria-hidden="true" class="icon icon-left"></i>
            </span>
          `, disabled),
        },
      ];
    };

    const buildNextButtons = () => {
      const disabled = currentLotNumber === totalNumberOfLots;
      return [
        {
          action: actions.goToNextLot.bind(null, { emit, eventName }),
          placeholderClass: 'p-next-page',
          content: buttonPlaceholder(`
            <span>
              Page suivante
            </span>
            <i aria-hidden="true" class="icon icon-right"></i>
          `, disabled),
        },
        {
          action: actions.goToLastLot.bind(null, { emit, eventName }),
          placeholderClass: 'p-last-page',
          content: buttonPlaceholder(`
            <span>
              <span class="sr-only">
                Aller à la dernière page
              </span>
              <i aria-hidden="true" class="sas-icon sas-icon-last"></i>
            </span>
          `, disabled),
        },
      ];
    };

    const buildPageNumberButtons = () => {
      const buildCurrentButton = () => (
        {
          placeholderClass: 'p-item p-active',
          content: `
            <button type="button" disabled aria-label="Page ${currentLotNumber}" aria-current="page">
              ${currentLotNumber}
            </button>
          `,
          action: () => { },
        }
      );

      const buildNumberButton = (number) => (
        {
          action: actions.goToLotOfNumber.bind(null, { targetLot: number, emit, eventName }),
          placeholderClass: 'p-item',
          content: buttonPlaceholder(`
            <span class="sr-only">
              Aller à la page ${number}
            </span>
            ${number}
          `),
        }
      );

      switch (currentLotNumber) {
        case 1:
          return [
            buildCurrentButton(),
            buildNumberButton(2),
          ];
        case totalNumberOfLots:
          return [
            buildNumberButton(totalNumberOfLots - 1),
            buildCurrentButton(),
          ];
        default:
          return [
            buildNumberButton(currentLotNumber - 1),
            buildCurrentButton(),
            buildNumberButton(currentLotNumber + 1),
          ];
      }
    };

    return [
      ...buildBackButtons(),
      ...buildPageNumberButtons(),
      ...buildNextButtons(),
    ];
  }
}
