import Injector from 'lib/Injector';
import ElementActions from 'components/ElementActions';

window.document.addEventListener('DOMContentLoaded', () => {
  Injector.component.registerMany({
    ElementActions
  }, { force: true })
});
