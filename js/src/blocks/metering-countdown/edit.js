/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

/**
 * All three messages are plain text and edited in the sidebar. The canvas shows a
 * preview of the message visitors see while they still have several free articles.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {
  const { template, singularTemplate, lastArticleTemplate } = attributes;
  const preview =
    template || __( 'You have {count} free articles left.', 'memberful' );

  return (
    <>
      <InspectorControls>
        <PanelBody title={ __( 'Countdown messages', 'memberful' ) }>
          <TextControl
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            label={ __( 'Articles left message', 'memberful' ) }
            help={ __(
              'Shown while more than one free article remains after this one. Use {count} for the number.',
              'memberful'
            ) }
            value={ template }
            onChange={ ( value ) => setAttributes( { template: value } ) }
            placeholder={ __(
              'You have {count} free articles left.',
              'memberful'
            ) }
          />
          <TextControl
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            label={ __( 'One article left message', 'memberful' ) }
            help={ __(
              'Shown when exactly one free article remains after this one. Leave empty to hide the block at that point.',
              'memberful'
            ) }
            value={ singularTemplate }
            onChange={ ( value ) =>
              setAttributes( { singularTemplate: value } )
            }
            placeholder={ __(
              'You have {count} free article left.',
              'memberful'
            ) }
          />
          <TextControl
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            label={ __( 'Last article message', 'memberful' ) }
            help={ __(
              'Shown instead of the countdown when the visitor is reading their last free article. Leave empty to hide the block on the last article.',
              'memberful'
            ) }
            value={ lastArticleTemplate }
            onChange={ ( value ) =>
              setAttributes( { lastArticleTemplate: value } )
            }
            placeholder={ __(
              'This is your last free article.',
              'memberful'
            ) }
          />
        </PanelBody>
      </InspectorControls>
      <p { ...useBlockProps() }>{ preview }</p>
    </>
  );
}
