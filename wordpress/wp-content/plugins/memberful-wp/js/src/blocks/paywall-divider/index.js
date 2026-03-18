import { registerBlockType } from "@wordpress/blocks";
import { useBlockProps } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
import metadata from "./block.json";

registerBlockType(metadata.name, {
  edit: () => {
    const blockProps = useBlockProps({
      className: "memberful-paywall-divider",
    });

    return (
      <div
        {...blockProps}
        style={{
          borderTop: "2px dashed #8c8f94",
          margin: "16px 0",
          paddingTop: "8px",
        }}
      >
        <strong>{__("Protected content below this line", "memberful")}</strong>
      </div>
    );
  },
  save: () => null,
});
