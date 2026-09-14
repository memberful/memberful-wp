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
import {
  BlockControls,
  InspectorControls,
  RichText,
  store as blockEditorStore,
  useBlockProps,
} from '@wordpress/block-editor';
import {
  PanelBody,
  TextControl,
  ToolbarButton,
  ToolbarGroup,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { create, insert, toHTMLString } from '@wordpress/rich-text';

const COUNT_PLACEHOLDER = '{count}';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit( { attributes, setAttributes, clientId } ) {
  const { template, singularTemplate, lastArticleTemplate } = attributes;
  const { selectionStart, selectionEnd } = useSelect(
    ( select ) => {
      const { getSelectionStart, getSelectionEnd } =
        select( blockEditorStore );
      const start = getSelectionStart();
      const end = getSelectionEnd();

      if (
        start.clientId !== clientId ||
        end.clientId !== clientId ||
        start.attributeKey !== 'template' ||
        end.attributeKey !== 'template'
      ) {
        return {};
      }

      return {
        selectionStart: start.offset,
        selectionEnd: end.offset,
      };
    },
    [ clientId ]
  );

  const insertCountPlaceholder = () => {
    const value = create( { html: template || '' } );
    const selectedStart = Number.isInteger( selectionStart )
      ? selectionStart
      : value.text.length;
    const selectedEnd = Number.isInteger( selectionEnd )
      ? selectionEnd
      : selectedStart;
    const insertStart = Math.max(
      0,
      Math.min( selectedStart, selectedEnd, value.text.length )
    );
    const insertEnd = Math.max(
      0,
      Math.min( Math.max( selectedStart, selectedEnd ), value.text.length )
    );
    const nextValue = insert(
      value,
      COUNT_PLACEHOLDER,
      insertStart,
      insertEnd
    );

    setAttributes( {
      template: toHTMLString( { value: nextValue } ),
    } );
  };

  return (
    <>
      <InspectorControls>
        <PanelBody title={ __( 'Countdown messages', 'memberful' ) }>
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
      <BlockControls group="block">
        <ToolbarGroup>
          <ToolbarButton
            title={ __( 'Insert count placeholder', 'memberful' ) }
            onClick={ insertCountPlaceholder }
          >
            { COUNT_PLACEHOLDER }
          </ToolbarButton>
        </ToolbarGroup>
      </BlockControls>
      <RichText
        { ...useBlockProps() }
        identifier="template"
        tagName="p"
        value={ template }
        onChange={ ( value ) => setAttributes( { template: value } ) }
        placeholder={ __(
          'You have {count} free articles left.',
          'memberful'
        ) }
      />
    </>
  );
}
