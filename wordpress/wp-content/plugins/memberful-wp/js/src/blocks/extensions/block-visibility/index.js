import {
  ToggleControl,
  SelectControl,
  CheckboxControl,
  Notice,
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { createHigherOrderComponent } from "@wordpress/compose";
import { addFilter } from "@wordpress/hooks";
import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody } from "@wordpress/components";

const subscriptionPlans =
  window?.memberful_wp_block_editor?.options?.memberful_subscriptions || [];
const excludedBlocks =
  window?.memberful_wp_block_editor?.block_visibility_excluded_blocks || [];

// Add the visibility attributes to the block settings
const MemberfulVisibilityAttributes = (settings, name) => {
  if (typeof settings.attributes !== "undefined") {
    if (!excludedBlocks.includes(name)) {
      settings.attributes = Object.assign(settings.attributes, {
        memberful_visibility: {
          type: "string",
          enum: ["none", "logged_in", "specific"],
          default: "none",
        },
        memberful_visibility_hide: {
          type: "boolean",
          default: false,
        },
        memberful_visibility_plans: {
          type: "array",
          default: [],
        },
      });
    }
  }
  return settings;
};

addFilter(
  "blocks.registerBlockType",
  "memberful/block-visibility",
  MemberfulVisibilityAttributes,
);

// Add the visibility controls to the allowed blocks.
const MemberfulVisibilityControlsOptions = (props) => {
  const { attributes, setAttributes } = props;
  const {
    memberful_visibility,
    memberful_visibility_hide,
    memberful_visibility_plans,
  } = attributes;

  const handleVisibilityChange = (value) => {
    if (value === "none" || !value) {
      setAttributes({ memberful_visibility_hide: false });
    }
    setAttributes({
      memberful_visibility: value,
      memberful_visibility_plans: [],
    });
  };

  const toggleVisibilityHide = () =>
    setAttributes({ memberful_visibility_hide: !memberful_visibility_hide });

  const handleSpecificPlansChange = (id, value) => {
    if (value) {
      setAttributes({
        memberful_visibility_plans: [...memberful_visibility_plans, id],
      });
    } else {
      setAttributes({
        memberful_visibility_plans: memberful_visibility_plans.filter(
          (planId) => planId !== id,
        ),
      });
    }
  };

  return (
    <>
      <SelectControl
        label={
          memberful_visibility_hide
            ? __("Hide content from:", "memberful")
            : __("Show content to:", "memberful")
        }
        value={memberful_visibility}
        onChange={handleVisibilityChange}
        options={[
          { value: "none", label: __("All users (Default)", "memberful") },
          {
            value: "logged_in",
            label: __("All logged-in members", "memberful"),
          },
          {
            value: "specific",
            label: __("Specific membership plan", "memberful"),
          },
        ]}
      />
      {memberful_visibility === "specific" && (
        <>
          <p
            className="components-base-control__help"
            style={{ color: "rgb(117, 117, 117)", fontSize: "12px" }}
          >
            {__(
              "Applies to members with an active subscription to at least one of the following plans:",
              "memberful",
            )}
          </p>
          {Object.entries(subscriptionPlans).map(([id, plan]) => (
            <CheckboxControl
              key={id}
              label={plan.name}
              checked={memberful_visibility_plans.includes(id)}
              onChange={(value) => handleSpecificPlansChange(id, value)}
            />
          ))}
          {memberful_visibility_plans.length === 0 && (
            <Notice status="error" isDismissible={false}>
              {__("Please select at least one plan.", "memberful")}
            </Notice>
          )}
        </>
      )}
      {memberful_visibility !== "none" && (
        <ToggleControl
          label={__("Hide when the above conditions are met", "memberful")}
          checked={memberful_visibility_hide}
          onChange={toggleVisibilityHide}
        />
      )}
    </>
  );
};

// Render the visibility controls panel in the block sidebar UI.
const MemberfulVisibilityControls = createHigherOrderComponent((BlockEdit) => {
  return (props) => {
    return (
      <>
        <BlockEdit key="edit" {...props} />
        {props.isSelected && !excludedBlocks.includes(props.name) && (
          <InspectorControls>
            <PanelBody
              title={__("Memberful Visibility", "memberful")}
              initialOpen={false}
            >
              {MemberfulVisibilityControlsOptions(props)}
            </PanelBody>
          </InspectorControls>
        )}
      </>
    );
  };
}, "MemberfulVisibilityControls");

addFilter(
  "editor.BlockEdit",
  "memberful/block-visibility",
  MemberfulVisibilityControls,
);
