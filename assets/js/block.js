const { registerBlockType } = wp.blocks;
const { useBlockProps, InspectorControls } = wp.blockEditor;
const { useSelect } = wp.data;
const { createElement, useState, useEffect } = wp.element;
const { PanelBody, SelectControl, TextControl, ToggleControl } = wp.components;
const { __ } = wp.i18n;

registerBlockType('locp/table-of-contents', {
    title: __('Table of Contents', 'list-of-contents'),
    icon: 'list-view',
    category: 'widgets',
    attributes: {
        design: {
            type: 'string',
            default: 'design1'
        },
        headingText: {
            type: 'string',
            default: 'Table of Contents'
        },
        enableToggle: {
            type: 'boolean',
            default: false
        },
        position: {
            type: 'string',
            default: 'after_first_paragraph'
        }
    },

    edit({ attributes, setAttributes, clientId }) {
        const { design, headingText, enableToggle, position } = attributes;
        const blockProps = useBlockProps({ className: `locp-toc-block ${design}` });

        const [headings, setHeadings] = useState([]);

        // Get headings from the current post content
        const postContent = useSelect((select) => {
            const blocks = select('core/block-editor').getBlocks();
            if (!blocks) return [];
            return blocks
                .filter(block => block.name === 'core/heading')
                .map(block => ({
                    id: block.clientId,
                    content: block.attributes.content || '',
                    level: block.attributes.level || 2,
                }));
        }, []);

        // Update headings when post content changes
        useEffect(() => {
            setHeadings(postContent);
        }, [postContent]);

        if (!headings || headings.length === 0) {
            return createElement(
                'div', 
                blockProps, 
                createElement('div', { className: 'locp-toc-placeholder' },
                    createElement('p', null, __('No headings found in this post. Add some headings to see the table of contents.', 'list-of-contents'))
                )
            );
        }

        return createElement(
            'div',
            null,
            createElement(InspectorControls, null,
                createElement(PanelBody, { title: __('Table of Contents Settings', 'list-of-contents'), initialOpen: true },
                    createElement(SelectControl, {
                        label: __('Design Style', 'list-of-contents'),
                        value: design,
                        options: [
                            { label: __('Design 1', 'list-of-contents'), value: 'design1' },
                            { label: __('Design 2', 'list-of-contents'), value: 'design2' },
                            { label: __('Design 3', 'list-of-contents'), value: 'design3' },
                            { label: __('Design 4 (Two Columns)', 'list-of-contents'), value: 'design4' },
                            { label: __('Design 5 (Two Columns with order)', 'list-of-contents'), value: 'design5' },
                            { label: __('Design 6 (Right hand corner)', 'list-of-contents'), value: 'design6' }
                        ],
                        onChange: (value) => setAttributes({ design: value })
                    }),
                    createElement(TextControl, {
                        label: __('Heading Text', 'list-of-contents'),
                        value: headingText,
                        onChange: (value) => setAttributes({ headingText: value })
                    }),
                    createElement(ToggleControl, {
                        label: __('Enable Toggle', 'list-of-contents'),
                        checked: enableToggle,
                        onChange: (value) => setAttributes({ enableToggle: value })
                    }),
                    createElement(SelectControl, {
                        label: __('Position', 'list-of-contents'),
                        value: position,
                        options: [
                            { label: __('After First Paragraph', 'list-of-contents'), value: 'after_first_paragraph' },
                            { label: __('Before Content', 'list-of-contents'), value: 'before_content' },
                            { label: __('After Content', 'list-of-contents'), value: 'after_content' }
                        ],
                        onChange: (value) => setAttributes({ position: value })
                    })
                )
            ),
            createElement(
                'div',
                blockProps,
                createElement('h2', { className: 'locp-toc-title' }, headingText),
                createElement(
                    'nav',
                    { className: 'locp-toc-nav' },
                    createElement(
                        'ol',
                        { className: 'locp-toc-list' },
                        headings.map((heading, index) =>
                            createElement(
                                'li',
                                { key: index, className: `locp-toc-item level-${heading.level}` },
                                createElement(
                                    'a',
                                    { 
                                        href: `#${heading.id}`,
                                        className: 'locp-toc-link'
                                    },
                                    heading.content
                                )
                            )
                        )
                    )
                )
            )
        );
    },

    save() {
        return null; // Server-side rendering
    }
});
