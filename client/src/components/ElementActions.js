import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { compose } from 'redux';
import { DropdownItem } from 'reactstrap';
import { inject } from 'lib/Injector';
import { elementType } from 'types/elementType';
import { elementTypeType } from 'types/elementTypeType';
import AbstractAction from 'components/ElementActions/AbstractAction';

/**
 * Element actions is a dropdown menu containing links to inline editing forms for each
 * of the element's primary tabs, as well as operations such as save, publish, archive etc
 */
class ElementActions extends Component {
  constructor(props) {
    super(props);
    this.handleEditTabsClick = this.handleEditTabsClick.bind(this);
  }

  /**
   * Set the active tab
   *
   * @param {Object} event
   */
  handleEditTabsClick(event) {
    const { handleEditTabsClick } = this.props;
     handleEditTabsClick(event.target.name);
  }

  handleEditFormLinkClick(event) {
    console.log(event.target.getAttribute('data-test'));
    window.location = event.target.getAttribute('data-test');
  }

  renderEditFormLink() {
    const { element } = this.props;
    const editFormURL = element.blockSchema.actions.edit;
    return (
      <AbstractAction
        key="edit-form-link-name"
        name="edit-form-link-name"
        title="Edit"
        data-test={ editFormURL }
        onClick={this.handleEditFormLinkClick}
        active={ false }
      />
    );
  }

  renderEditFormLinkDivider() {
    const { editTabs } = this.props;

    if (!editTabs || !editTabs.length) {
      return null;
    }
    return <DropdownItem divider role="separator" />;
  }

  /**
   * Render buttons for the edit form tabs that will be a part of the edit form (if they exist)
   *
   * @returns {HTMLElement[]|null}
   */
  renderEditTabs() {
    const { editTabs, activeTab, type } = this.props;

    if (!editTabs || !editTabs.length) {
      return null;
    }

    return editTabs.map(
      ({ name, title }) =>
        (<AbstractAction
          key={name}
          name={name}
          title={title}
          type={type}
          onClick={this.handleEditTabsClick}
          active={name === activeTab}
        />)
    );
  }

  /**
   * Renders a divider if there are CMS edit tabs and child actions
   *
   * @returns {DropdownItem|null}
   */
  renderDivider() {
    const { children, editTabs } = this.props;

    if (editTabs && editTabs.length && React.Children.count(children)) {
      return <DropdownItem divider role="separator" />;
    }
    return null;
  }

  /**
   * If inline editing is enabled, render the "more actions" menu. Injector registrations can
   * define HOCs that add action components as children of this component.
   *
   * @returns {ActionMenuComponent|null}
   */
  render() {
    const { children, id, ActionMenuComponent } = this.props;

    const dropdownToggleClassNames = [
      'element-editor-header__actions-toggle',
      'btn',
      'btn-sm',
      'btn--no-text',
      'font-icon-dot-3',
    ];

    return (
      <ActionMenuComponent
        id={`element-editor-actions-${id}`}
        className={'element-editor-header__actions-dropdown'}
        dropdownMenuProps={{ right: true }}
        dropdownToggleClassNames={dropdownToggleClassNames}
      >
        { this.renderEditTabs() }
        { this.renderEditFormLinkDivider() }
        { this.renderEditFormLink() }
        { this.renderDivider() }
        { children }
      </ActionMenuComponent>
    );
  }
}

// There's some extra prop types in here for registered transformations to consume
ElementActions.propTypes = {
  // eslint-disable-next-line react/no-unused-prop-types
  element: elementType,
  type: elementTypeType.isRequired,
  // eslint-disable-next-line react/no-unused-prop-types
  areaId: PropTypes.number.isRequired,
  activeTab: PropTypes.string,
  editTabs: PropTypes.arrayOf(PropTypes.shape({
    title: PropTypes.string,
    name: PropTypes.string,
  })),
  handleEditTabsClick: PropTypes.func.isRequired,
};

ElementActions.defaultProps = {
  editTabs: [],
};

export { ElementActions as Component };

export default compose(
  inject(
    ['ActionMenu'],
    (ActionMenuComponent) => ({
      ActionMenuComponent,
    }),
    () => 'ElementEditor.ElementList.Element'
  )
)(ElementActions);
