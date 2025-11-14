import { ToggleControl, SelectControl, ComboboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter, applyFilters } from '@wordpress/hooks';
import { useEffect } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';

// TODO: Get from Memberful:
// - All available plans
// - Any excluded blocks?

const MemberfulVisibilityControlsOptions = (props) => {
    const { attributes, setAttributes } = props;
    const { memberfulVisibility, memberfulVisibilityHide, memberfulVisibilitySpecificPlan } = attributes;

    return (
        <>
            <SelectControl
                label={__('Visibility', 'memberful')}
                value={memberfulVisibility}
                onChange={(value) => setAttributes({ memberfulVisibility: value })}
                options={[
                    { value: 'none', label: __('None (All users)', 'memberful') },
                    { value: 'all', label: __('All members (active, inactive, or free)', 'memberful') },
                    { value: 'active', label: __('Members with any active plan', 'memberful') },
                    { value: 'specific', label: __('Members with a specific plan', 'memberful') },
                ]}
            />
            {memberfulVisibility === 'specific' && (
                <>
                    <p className="components-base-control__help" style={{ color: 'rgb(117, 117, 117)', fontSize: '12px' }}>
                        {__('Show the block to members with at least one of the following plans:', 'memberful')}
                    </p>
                    <ComboboxControl
                        label={__('Select plan(s)', 'memberful')}
                        options={[
                            { value: 'plan_1', label: __('Plan 1', 'memberful') },
                            { value: 'plan_2', label: __('Plan 2', 'memberful') },
                            { value: 'plan_3', label: __('Plan 3', 'memberful') },
                        ]}
                    />
                </>
            )}
            <ToggleControl
                label={__('Hide when the above conditions are met', 'memberful')}
                checked={memberfulVisibilityHide}
                onChange={(value) => setAttributes({ memberfulVisibilityHide: value })}
            />
        </>
    );
};

const MemberfulVisibilityControls = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        const { attributes, setAttributes } = props;
        const { blockType } = props;

        let excludedBlocks = [];

        excludedBlocks = applyFilters('memberful.excludedBlocksBlockVisibility', excludedBlocks);

        return (
            <>
                <BlockEdit key="edit" {...props} />
                {!excludedBlocks.includes(blockType) && (
                    <InspectorControls>
                        <PanelBody title="Memberful Visibility">
                            {MemberfulVisibilityControlsOptions(props)}
                        </PanelBody>
                    </InspectorControls>
                )}
            </>
        );
    };
}, 'MemberfulVisibilityControls');

addFilter('editor.BlockEdit', 'memberful/block-visibility', MemberfulVisibilityControls);
