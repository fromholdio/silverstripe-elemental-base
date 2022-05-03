import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';
import i18n from 'i18n';

class Summary extends PureComponent {
  /**
   * Renders a preview of the Element's content, allowing for either a simple
   * image, or a section of text content (or both). For an Element with only a
   * file, we do NOT wish to render the "no preview available" filler content.
   * Elements with content and/or a file should always show the text and/or
   * the image.
   *  - if there is a file, show that.
   *  - if there is text, show that.
   *  - if there is a file and text show both.
   *  - if there is no file, and no text, substitute and show "no preview" text.
   */
  render() {
    const { fileUrl, fileTitle, content } = this.props;
    const noContent = i18n._t('ElementSummary.NO_PREVIEW', 'No preview available');

    if (!fileUrl && !fileTitle && !content) {
        return ( <span></span> );
    }

    return (

      <div className="element-editor-summary">
        {fileUrl &&
        <img
          className="element-editor-summary__thumbnail-image"
          src={fileUrl}
          alt={fileTitle}
        />
        }
        {(content || !fileUrl) &&
        <p className="element-editor-summary__content">
          {content || noContent}
        </p>
        }
      </div>
    );
  }
}

Summary.defaultProps = {};

Summary.propTypes = {
  content: PropTypes.string,
  fileUrl: PropTypes.string,
  fileTitle: PropTypes.string,
};

export default Summary;
