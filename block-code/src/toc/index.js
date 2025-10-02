import { registerBlockType } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { 
    InspectorControls, 
    useBlockProps,
    RichText 
} from '@wordpress/block-editor';
import { 
    PanelBody, 
    ToggleControl,
    SelectControl 
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

// Helper function to extract headings from blocks
const extractHeadings = (blocks, settings) => {
    const headings = [];
    
    const processBlocks = (blockList) => {
        blockList.forEach(block => {
            if (block.name === 'core/heading') {
                const level = block.attributes.level || 1;
                const content = block.attributes.content || '';
                const anchor = block.attributes.anchor || '';
                
                // Check if this heading level should be included
                const shouldInclude = settings[`includeH${level}`];
                
                if (shouldInclude && content.trim()) {
                    // Generate anchor if not exists
                    const headingAnchor = anchor || generateAnchor(content);
                    
                    headings.push({
                        level,
                        content: stripHtmlTags(content),
                        anchor: headingAnchor,
                        clientId: block.clientId
                    });
                }
            }
            
            // Process inner blocks recursively
            if (block.innerBlocks && block.innerBlocks.length > 0) {
                processBlocks(block.innerBlocks);
            }
        });
    };
    
    processBlocks(blocks);
    return headings;
};

// Helper function to strip HTML tags
const stripHtmlTags = (html) => {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
};

// Helper function to generate anchor from text
const generateAnchor = (text) => {
    return stripHtmlTags(text)
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim();
};

// Edit component
const Edit = ({ attributes, setAttributes }) => {
    console.log('Edit lOC component', attributes, setAttributes);
    const { 
        headingText, 
        enableToggle, 
        design,
        includeH1,
        includeH2,
        includeH3,
        includeH4,
        includeH5,
        includeH6
    } = attributes;
    
    const [headings, setHeadings] = useState([]);
    
    // Get all blocks from the editor
    // const blocks = useSelect(select => {
    //     return select('core/block-editor').getBlocks();
    // }, []);
    const blocks = useSelect(
        (select) => select('core/block-editor').getBlocks(), [] // ❌ don’t pass empty array
    );
    
    // Extract headings when blocks change
    useEffect(() => {
        const headingSettings = {
            includeH1,
            includeH2,
            includeH3,
            includeH4,
            includeH5,
            includeH6
        };
        
        const extractedHeadings = extractHeadings(blocks, headingSettings);
        setHeadings(extractedHeadings);
    }, [blocks, includeH1, includeH2, includeH3, includeH4, includeH5, includeH6]);
    
    const blockProps = useBlockProps({
        className: `locp-toc-block ${design}`
    });
    
    // Render TOC List
    const renderTOCList = () => {
        if (headings.length === 0) {
            return (
                <div class="locp-toc-block toc-${design}">
                    <p className="toc-no-headings">
                        {__('No headings found. Add some heading blocks to generate the table of contents.', 'list-of-contents')}
                    </p>
                </div>
            );
        }
        
        return (
            <ol className="toc-list">
                {headings.map((heading, index) => (
                    <li 
                        key={index} 
                        className={`toc-item toc-level-${heading.level}`}
                        style={{ marginLeft: `${(heading.level - 1) * 20}px` }}
                    >
                        <a 
                            href={'#block-'+heading.clientId}
                            className="toc-link"
                            data-id={`#${heading.anchor}`}
                            onClick={(e) => e.preventDefault()}
                        >
                            {heading.content}
                        </a>
                    </li>
                ))}
            </ol>
        );
    };
    
    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Table of Contents Settings', 'list-of-contents')}>
                    <SelectControl
                        label={__('Design', 'list-of-contents')}
                        value={design}
                        options={[
                            { label: __('Design 1', 'list-of-contents'), value: 'design1' },
                            { label: __('Design 2', 'list-of-contents'), value: 'design2' },
                            { label: __('Design 3', 'list-of-contents'), value: 'design3' },
                            { label: __('Design 4 (Two Columns)', 'list-of-contents'), value: 'design4' },
                            { label: __('Design 5 (Two Columns with order)', 'list-of-contents'), value: 'design5' }, 
                            { label: __('Design 6 (Right hand cornor)', 'list-of-contents'), value: 'design6' }
                        ]}
                        onChange={(value) => setAttributes({ design: value })}
                    />
                    
                    <ToggleControl
                        label={__('Enable Toggle', 'list-of-contents')}
                        checked={enableToggle}
                        onChange={(value) => setAttributes({ enableToggle: value })}
                        help={__('Allow users to collapse/expand the table of contents', 'list-of-contents')}
                    />
                </PanelBody>
                
                <PanelBody title={__('Heading Levels', 'list-of-contents')} initialOpen={false}>
                    <ToggleControl
                        label={__('Include H1', 'list-of-contents')}
                        checked={includeH1}
                        onChange={(value) => setAttributes({ includeH1: value })}
                    />
                    <ToggleControl
                        label={__('Include H2', 'list-of-contents')}
                        checked={includeH2}
                        onChange={(value) => setAttributes({ includeH2: value })}
                    />
                    <ToggleControl
                        label={__('Include H3', 'list-of-contents')}
                        checked={includeH3}
                        onChange={(value) => setAttributes({ includeH3: value })}
                    />
                    <ToggleControl
                        label={__('Include H4', 'list-of-contents')}
                        checked={includeH4}
                        onChange={(value) => setAttributes({ includeH4: value })}
                    />
                    <ToggleControl
                        label={__('Include H5', 'list-of-contents')}
                        checked={includeH5}
                        onChange={(value) => setAttributes({ includeH5: value })}
                    />
                    <ToggleControl
                        label={__('Include H6', 'list-of-contents')}
                        checked={includeH6}
                        onChange={(value) => setAttributes({ includeH6: value })}
                    />
                </PanelBody>
            </InspectorControls>
            
            <div  {...blockProps}>
                <div className="toc-header list-table-of-contents">
                    <RichText
                        tagName="h3"
                        className="toc-heading"
                        value={headingText}
                        onChange={(value) => setAttributes({ headingText: value })}
                        placeholder={__('Table of Contents', 'list-of-contents')}
                    />
                    {enableToggle && (
                        <button 
                            className="toc-toggle"
                            onClick={(e) => e.preventDefault()}
                        >
                            [toggle]
                        </button>
                    )}
                </div>
                
                <div className="toc-content">
                    {renderTOCList()}
                </div>
            </div>
        </>
    );
};

// Save component
const Save = ({ attributes }) => {
    console.log('Save lOC component');
    const { 
        headingText, 
        enableToggle, 
        design 
    } = attributes;
    
    const blockProps = useBlockProps.save({
        className: `locp-toc-block ${design}`
    });
    
    return (
        <div {...blockProps}>
            <div className="toc-header">
                <RichText.Content
                    tagName="h3"
                    className="toc-heading"
                    value={headingText}
                />
                {enableToggle && (
                    <button className="toc-toggle" aria-expanded="true">
                        <span className="toc-toggle-text">{__('Hide', 'list-of-contents')}</span>
                    </button>
                )}
            </div>
            
            <div className="toc-content" data-toc-content="">
                {/* Content will be generated by view.js on frontend */}
            </div>
        </div>
    );
};

registerBlockType(metadata.name, {
    edit: Edit,
    save: Save,
});
