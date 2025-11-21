import { ToggleControl, SelectControl, CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';

const subscriptionPlans = window?.memberful_wp_block_editor?.options?.memberful_subscriptions || [];
const excludedBlocks = window?.memberful_wp_block_editor?.block_visibility_excluded_blocks || [];

// Add custom attributes to allowed blocks for visibility controls.
const MemberfulBlockVisibilityAttributes = (settings, name) => {
    if (excludedBlocks.includes(name)) {
        return settings;
    }
    if (settings.attributes) {
        settings.attributes = Object.assign(settings.attributes, {
            memberful_visibility: {
                enum: ['none', 'all', 'active', 'specific'],
            },
            memberful_visibility_hide: {
                type: 'boolean',
                default: false,
            },
            memberful_visibility_plans: {
                type: 'array',
                default: [],
            },
        });
    }
    return settings;
};

addFilter('blocks.registerBlockType', 'memberful/block-visibility-attributes', MemberfulBlockVisibilityAttributes);

// Add the visibility controls to the allowed blocks.
const MemberfulVisibilityControlsOptions = (props) => {
    const { attributes, setAttributes } = props;
    const { memberful_visibility, memberful_visibility_hide, memberful_visibility_plans } = attributes;

    const handleVisibilityChange = (value) => {
        // Reset the specific plans if any are selected.
        if (value !== 'specific') {
            setAttributes({ memberful_visibility_plans: [] });
        }
        setAttributes({ memberful_visibility: value });
    };

    const toggleVisibilityHide = () => setAttributes({ memberful_visibility_hide: !memberful_visibility_hide });

    const handleSpecificPlansChange = (id, value) => {
        if (value) {
            setAttributes({ memberful_visibility_plans: [...memberful_visibility_plans, id] });
        } else {
            setAttributes({ memberful_visibility_plans: memberful_visibility_plans.filter((planId) => planId !== id) });
        }
    };

    return (
        <>
            <SelectControl
                label={__('Visibility Condition', 'memberful')}
                value={memberful_visibility}
                onChange={handleVisibilityChange}
                options={[
                    { value: 'none', label: __('None (All users)', 'memberful') },
                    { value: 'all', label: __('All members (active, inactive, or free)', 'memberful') },
                    { value: 'active', label: __('Members with any active plan', 'memberful') },
                    { value: 'specific', label: __('Members with a specific plan', 'memberful') },
                ]}
            />
            {memberful_visibility === 'specific' && (
                <>
                    <p className="components-base-control__help" style={{ color: 'rgb(117, 117, 117)', fontSize: '12px' }}>
                        {__('Show the block to members with at least one of the following plans:', 'memberful')}
                    </p>
                    {Object.entries(subscriptionPlans).map(([id, plan]) => (
                        <CheckboxControl
                            key={id}
                            label={plan.name}
                            checked={memberful_visibility_plans.includes(id)}
                            onChange={(value) => handleSpecificPlansChange(id, value)}
                        />
                    ))}
                </>
            )}
            {memberful_visibility !== 'none' && (
                <ToggleControl
                    label={__('Hide when the above conditions are met', 'memberful')}
                    checked={memberful_visibility_hide}
                    onChange={toggleVisibilityHide}
                />
            )}
        </>
    );
};

const MemberfulVisibilityControls = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        return (
            <>
                <BlockEdit key="edit" {...props} />
                {props.isSelected && !excludedBlocks.includes(props.name) && (
                    <InspectorControls>
                        <PanelBody title={__('Memberful Visibility', 'memberful')} initialOpen={false}>
                            {MemberfulVisibilityControlsOptions(props)}
                        </PanelBody>
                    </InspectorControls>
                )}
            </>
        );
    };
}, 'MemberfulVisibilityControls');

addFilter('editor.BlockEdit', 'memberful/block-visibility', MemberfulVisibilityControls);
