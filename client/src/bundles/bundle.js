import Injector from 'lib/Injector';
import ElementList from 'components/ElementList';
import Element from 'components/Element';
import ColumnSize from 'components/ColumnSize';
import ElementActions from 'components/ElementActions';

window.document.addEventListener('DOMContentLoaded', () => {
  Injector.component.registerMany({
    ElementList,
    Element,
    ColumnSize,
    ElementActions
  }, { force: true })
});
