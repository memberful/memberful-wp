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
          borderTop: "2px dashed currentColor",
          margin: "16px 0",
          paddingTop: "8px",
          opacity: 0.6,
        }}
      >
        <strong>{__("Protected content below this line", "memberful")}</strong>
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          width="18"
          height="18"
          fill="currentColor"
          style={{ marginLeft: "6px", verticalAlign: "middle" }}
          aria-hidden="true"
        >
          <path d="M12 15.5l-6-6 1.41-1.41L12 12.67l4.59-4.58L18 9.5z" />
        </svg>
      </div>
    );
  },
  save: () => null,
});
