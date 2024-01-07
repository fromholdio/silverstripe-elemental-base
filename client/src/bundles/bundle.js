import Injector from 'lib/Injector';
import ElementActions from 'components/ElementActions';
import Summary from 'components/ElementEditor/Summary';

window.document.addEventListener('DOMContentLoaded', () => {
  Injector.component.registerMany({
    ElementActions,
    ElementSummary: Summary,
  }, { force: true })
});
