import Injector from 'lib/Injector';
import ElementList from 'components/ElementList';
import Element from 'components/Element';
import ColumnSize from 'components/ColumnSize';
import ElementActions from 'components/ElementActions';
import Summary from 'components/ElementEditor/Summary';

window.document.addEventListener('DOMContentLoaded', () => {
  Injector.component.registerMany({
    ElementList,
    Element,
    ColumnSize,
    ElementActions,
    ElementSummary: Summary
  }, { force: true })
});
