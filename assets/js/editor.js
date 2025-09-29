/**
 * Paywall Anywhere - Editor JavaScript
 */

(function(wp, $) {
    'use strict';
    
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, TextControl, SelectControl, ToggleControl, RangeControl, Button, SearchControl } = wp.components;
    const { Fragment, useState } = wp.element;
    const { __ } = wp.i18n;
    const { useSelect, useDispatch } = wp.data;
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { registerShortcut } = wp.keyboardShortcuts || { registerShortcut: () => {} };
    
    /**
     * Gate Start Block
     */
    registerBlockType('paywall-anywhere/gate-start', {
        title: __('Paywall: Gate Start', 'paywall-anywhere'),
        description: __('Mark the start of premium content. Everything below this block will be locked.', 'paywall-anywhere'),
        icon: 'lock',
        category: 'widgets',
        keywords: [__('paywall'), __('premium'), __('lock')],
        attributes: {
            price: {
                type: 'number',
                default: 500
            },
            currency: {
                type: 'string',
                default: 'USD'
            },
            expiresDays: {
                type: 'number',
                default: 30
            },
            adFree: {
                type: 'boolean',
                default: false
            },
            customMessage: {
                type: 'string',
                default: ''
            }
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { price, currency, expiresDays, adFree, customMessage } = attributes;
            
            const blockProps = useBlockProps({
                className: 'paywall-anywhere-gate-start'
            });
            
            return (
                <Fragment>
                    <div {...blockProps}>
                        <div className="paywall-anywhere-gate-preview">
                            <span className="paywall-anywhere-gate-icon">🚪</span>
                            <h4>{__('Paywall Gate', 'paywall-anywhere')}</h4>
                            <p>{__('Content below this point will be locked', 'paywall-anywhere')}</p>
                            <div className="paywall-anywhere-gate-price">
                                {paywall_anywhere_format_price(price, currency)}
                            </div>
                        </div>
                    </div>
                    
                    <InspectorControls>
                        <PanelBody title={__('Paywall Settings', 'paywall-anywhere')} initialOpen={true}>
                            <RangeControl
                                label={__('Price (cents)', 'paywall-anywhere')}
                                value={price}
                                onChange={(value) => setAttributes({ price: value })}
                                min={0}
                                max={10000}
                                step={50}
                            />
                            
                            <SelectControl
                                label={__('Currency', 'paywall-anywhere')}
                                value={currency}
                                options={[
                                    { label: 'USD ($)', value: 'USD' },
                                    { label: 'EUR (€)', value: 'EUR' },
                                    { label: 'GBP (£)', value: 'GBP' },
                                    { label: 'JPY (¥)', value: 'JPY' }
                                ]}
                                onChange={(value) => setAttributes({ currency: value })}
                            />
                            
                            <RangeControl
                                label={__('Access Duration (Days)', 'paywall-anywhere')}
                                value={expiresDays}
                                onChange={(value) => setAttributes({ expiresDays: value })}
                                min={1}
                                max={365}
                            />
                            
                            <TextControl
                                label={__('Custom Message', 'paywall-anywhere')}
                                value={customMessage}
                                onChange={(value) => setAttributes({ customMessage: value })}
                                placeholder={__('Optional custom unlock message', 'paywall-anywhere')}
                            />
                            
                            <ToggleControl
                                label={__('Ad-Free for Premium Users', 'paywall-anywhere')}
                                checked={adFree}
                                onChange={(value) => setAttributes({ adFree: value })}
                            />
                        </PanelBody>
                    </InspectorControls>
                </Fragment>
            );
        },
        
        save: function() {
            // This is a dynamic block, so we return null and let PHP handle the rendering
            return null;
        }
    });
    
    /**
     * Unlock CTA Block
     */
    registerBlockType('paywall-anywhere/unlock-cta', {
        title: __('Paywall: Unlock Button', 'paywall-anywhere'),
        description: __('Display an unlock button for premium content.', 'paywall-anywhere'),
        icon: 'unlock',
        category: 'widgets',
        keywords: [__('unlock'), __('premium'), __('cta')],
        attributes: {
            itemId: {
                type: 'number',
                default: 0
            },
            providers: {
                type: 'array',
                default: ['stripe', 'woocommerce']
            },
            style: {
                type: 'string',
                default: 'filled'
            },
            text: {
                type: 'string',
                default: ''
            }
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { itemId, providers, style, text } = attributes;
            
            const blockProps = useBlockProps({
                className: 'paywall-anywhere-unlock-cta'
            });
            
            return (
                <Fragment>
                    <div {...blockProps}>
                        <div className="paywall-anywhere-cta-preview">
                            <button className={`paywall-anywhere-unlock-btn paywall-anywhere-unlock-btn-${style}`}>
                                {text || __('Unlock Content', 'paywall-anywhere')}
                            </button>
                        </div>
                    </div>
                    
                    <InspectorControls>
                        <PanelBody title={__('Button Settings', 'paywall-anywhere')} initialOpen={true}>
                            <TextControl
                                label={__('Item ID', 'paywall-anywhere')}
                                value={itemId}
                                onChange={(value) => setAttributes({ itemId: parseInt(value) || 0 })}
                                type="number"
                                help={__('The premium item ID to unlock', 'paywall-anywhere')}
                            />
                            
                            <TextControl
                                label={__('Button Text', 'paywall-anywhere')}
                                value={text}
                                onChange={(value) => setAttributes({ text: value })}
                                placeholder={__('Leave empty for default text', 'paywall-anywhere')}
                            />
                            
                            <SelectControl
                                label={__('Button Style', 'paywall-anywhere')}
                                value={style}
                                options={[
                                    { label: __('Filled', 'paywall-anywhere'), value: 'filled' },
                                    { label: __('Outline', 'paywall-anywhere'), value: 'outline' }
                                ]}
                                onChange={(value) => setAttributes({ style: value })}
                            />
                        </PanelBody>
                    </InspectorControls>
                </Fragment>
            );
        },
        
        save: function() {
            // This is a dynamic block, so we return null and let PHP handle the rendering
            return null;
        }
    });
    
    /**
     * Block Extensions for Individual Block Locking
     */
    
    /**
     * Add lock controls to all blocks
     */
    const withLockControls = createHigherOrderComponent((BlockEdit) => {
        return (props) => {
            if (!props.isSelected) {
                return <BlockEdit {...props} />;
            }
            
            const { attributes, setAttributes } = props;
            const isLocked = attributes.paywallAnywhereLocked || false;
            const price = attributes.paywallAnywherePrice || 500;
            const currency = attributes.paywallAnywhereCurrency || 'USD';
            const expiresDays = attributes.paywallAnywhereExpiresDays || 30;
            
            return (
                <Fragment>
                    <BlockEdit {...props} />
                    <InspectorControls>
                        <PanelBody title={__('Paywall Anywhere — Locking', 'paywall-anywhere')} initialOpen={false}>
                            <ToggleControl
                                label={__('Lock this block (Paywall Anywhere)', 'paywall-anywhere')}
                                checked={isLocked}
                                onChange={(value) => setAttributes({ paywallAnywhereLocked: value })}
                            />
                            
                            {isLocked && (
                                <Fragment>
                                    <RangeControl
                                        label={__('Price (cents)', 'paywall-anywhere')}
                                        value={price}
                                        onChange={(value) => setAttributes({ paywallAnywherePrice: value })}
                                        min={0}
                                        max={10000}
                                        step={50}
                                    />
                                    
                                    <SelectControl
                                        label={__('Currency', 'paywall-anywhere')}
                                        value={currency}
                                        options={[
                                            { label: 'USD ($)', value: 'USD' },
                                            { label: 'EUR (€)', value: 'EUR' },
                                            { label: 'GBP (£)', value: 'GBP' },
                                            { label: 'JPY (¥)', value: 'JPY' }
                                        ]}
                                        onChange={(value) => setAttributes({ paywallAnywhereCurrency: value })}
                                    />
                                    
                                    <RangeControl
                                        label={__('Access Duration (Days)', 'paywall-anywhere')}
                                        value={expiresDays}
                                        onChange={(value) => setAttributes({ paywallAnywhereExpiresDays: value })}
                                        min={1}
                                        max={365}
                                    />
                                </Fragment>
                            )}
                        </PanelBody>
                    </InspectorControls>
                </Fragment>
            );
        };
    }, 'withLockControls');
    
    addFilter('editor.BlockEdit', 'paywall-anywhere/with-lock-controls', withLockControls);
    
    /**
     * Add locked indicator to blocks
     */
    const withLockedIndicator = createHigherOrderComponent((BlockListBlock) => {
        return (props) => {
            const { attributes } = props;
            const isLocked = attributes.paywallAnywhereLocked || false;
            
            if (!isLocked) {
                return <BlockListBlock {...props} />;
            }
            
            return (
                <div className="paywall-anywhere-locked-block">
                    <div className="paywall-anywhere-locked-overlay">
                        <span className="paywall-anywhere-lock-icon">🔒</span>
                        <span className="paywall-anywhere-lock-text">
                            {__('Premium Block', 'paywall-anywhere')}
                        </span>
                    </div>
                    <BlockListBlock {...props} />
                </div>
            );
        };
    }, 'withLockedIndicator');
    
    addFilter('editor.BlockListBlock', 'paywall-anywhere/with-locked-indicator', withLockedIndicator);
    
    /**
     * Utility functions
     */
    function paywall_anywhere_format_price(priceMinor, currency) {
        const symbols = {
            'USD': '$',
            'EUR': '€',
            'GBP': '£',
            'JPY': '¥'
        };
        
        const symbol = symbols[currency] || '$';
        const divisor = (currency === 'JPY') ? 1 : 100;
        const price = priceMinor / divisor;
        
        return symbol + price.toFixed((currency === 'JPY') ? 0 : 2);
    }
    
    /**
     * Find & Lock Control Component
     */
    const FindAndLockControl = () => {
        const [searchQuery, setSearchQuery] = useState('');
        const [searchResults, setSearchResults] = useState([]);
        const [isSearching, setIsSearching] = useState(false);
        
        const { blocks } = useSelect((select) => ({
            blocks: select('core/block-editor').getBlocks()
        }));
        
        const { updateBlockAttributes } = useDispatch('core/block-editor');
        
        const searchBlocks = (query) => {
            if (!query.trim()) {
                setSearchResults([]);
                return;
            }
            
            setIsSearching(true);
            const results = [];
            
            const searchInBlocks = (blockList, path = []) => {
                blockList.forEach((block, index) => {
                    const currentPath = [...path, index];
                    
                    // Skip already locked blocks
                    if (block.attributes && block.attributes.paywallAnywhereLocked) {
                        return;
                    }
                    
                    // Search by block type
                    if (block.name && block.name.toLowerCase().includes(query.toLowerCase())) {
                        results.push({
                            id: block.clientId,
                            name: block.name,
                            path: currentPath,
                            content: block.name,
                            type: 'blockType'
                        });
                    }
                    
                    // Search in block content
                    if (block.attributes) {
                        Object.entries(block.attributes).forEach(([key, value]) => {
                            if (typeof value === 'string' && value.toLowerCase().includes(query.toLowerCase())) {
                                results.push({
                                    id: block.clientId,
                                    name: block.name,
                                    path: currentPath,
                                    content: value.substring(0, 100) + (value.length > 100 ? '...' : ''),
                                    type: 'content',
                                    attribute: key
                                });
                            }
                        });
                    }
                    
                    // Search in inner blocks
                    if (block.innerBlocks && block.innerBlocks.length > 0) {
                        searchInBlocks(block.innerBlocks, currentPath);
                    }
                });
            };
            
            searchInBlocks(blocks);
            setSearchResults(results);
            setIsSearching(false);
        };
        
        const lockBlock = (blockId) => {
            // Get default values (in a real implementation, these would come from settings)
            const defaultPrice = 500; // wp.data.select('core').getOption('paywall_anywhere_default_price') || 500;
            const defaultExpiry = 30; // wp.data.select('core').getOption('paywall_anywhere_default_expires_days') || 30;
            const defaultCurrency = 'USD'; // wp.data.select('core').getOption('paywall_anywhere_default_currency') || 'USD';
            
            updateBlockAttributes(blockId, {
                paywallAnywhereLocked: true,
                paywallAnywherePrice: defaultPrice,
                paywallAnywhereExpiresDays: defaultExpiry,
                paywallAnywhereCurrency: defaultCurrency
            });
            
            // Remove from search results after locking
            setSearchResults(results => results.filter(result => result.id !== blockId));
        };
        
        const scrollToBlock = (blockId) => {
            const blockElement = document.querySelector(`[data-block="${blockId}"]`);
            if (blockElement) {
                blockElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                blockElement.style.border = '2px solid #667eea';
                setTimeout(() => {
                    blockElement.style.border = '';
                }, 2000);
            }
        };
        
        return wp.element.createElement('div', { className: 'paywall-anywhere-find-lock-control' },
            wp.element.createElement('div', { className: 'paywall-anywhere-search-header' },
                wp.element.createElement('h4', null, __('Find & Lock Content', 'paywall-anywhere')),
                wp.element.createElement('p', null, __('Search for blocks to lock behind a paywall', 'paywall-anywhere'))
            ),
            
            wp.element.createElement('div', { className: 'paywall-anywhere-search-box' },
                wp.element.createElement(TextControl, {
                    placeholder: __('Search blocks by type or content...', 'paywall-anywhere'),
                    value: searchQuery,
                    onChange: (value) => {
                        setSearchQuery(value);
                        searchBlocks(value);
                    }
                })
            ),
            
            isSearching && wp.element.createElement('div', { className: 'paywall-anywhere-search-loading' },
                __('Searching...', 'paywall-anywhere')
            ),
            
            searchResults.length > 0 && wp.element.createElement('div', { className: 'paywall-anywhere-search-results' },
                wp.element.createElement('div', { className: 'paywall-anywhere-results-header' },
                    searchResults.length + ' ' + __('results found', 'paywall-anywhere')
                ),
                
                wp.element.createElement('div', { className: 'paywall-anywhere-results-list' },
                    searchResults.map((result, index) =>
                        wp.element.createElement('div', { 
                            key: `${result.id}-${index}`, 
                            className: 'paywall-anywhere-result-item' 
                        },
                            wp.element.createElement('div', { className: 'paywall-anywhere-result-content' },
                                wp.element.createElement('div', { className: 'paywall-anywhere-result-type' },
                                    result.name.replace('core/', '').replace('paywall-anywhere/', '')
                                ),
                                wp.element.createElement('div', { className: 'paywall-anywhere-result-text' },
                                    result.content
                                )
                            ),
                            
                            wp.element.createElement('div', { className: 'paywall-anywhere-result-actions' },
                                wp.element.createElement(Button, {
                                    isSmall: true,
                                    onClick: () => scrollToBlock(result.id)
                                }, __('Find', 'paywall-anywhere')),
                                
                                wp.element.createElement(Button, {
                                    isSmall: true,
                                    isPrimary: true,
                                    onClick: () => lockBlock(result.id)
                                }, __('Lock', 'paywall-anywhere'))
                            )
                        )
                    )
                )
            ),
            
            searchQuery && searchResults.length === 0 && !isSearching && wp.element.createElement('div', { className: 'paywall-anywhere-no-results' },
                __('No blocks found matching your search.', 'paywall-anywhere')
            )
        );
    };
    
    /**
     * Register the Find & Lock Document Setting Panel
     */
    if (wp.plugins && wp.editPost) {
        const { registerPlugin } = wp.plugins;
        const { PluginDocumentSettingPanel } = wp.editPost;
        
        registerPlugin('paywall-anywhere-find-lock', {
            render: () => {
                return wp.element.createElement(PluginDocumentSettingPanel, {
                    name: 'paywall-anywhere-find-lock',
                    title: __('Find & Lock Content', 'paywall-anywhere'),
                    className: 'paywall-anywhere-find-lock-panel'
                }, wp.element.createElement(FindAndLockControl));
            },
            icon: 'lock'
        });
    }
    
    /**
     * Locked Elements Panel Component
     */
    const LockedElementsPanel = () => {
        const { blocks } = useSelect((select) => ({
            blocks: select('core/block-editor').getBlocks()
        }));
        
        const { updateBlockAttributes } = useDispatch('core/block-editor');
        
        // Get locked blocks
        const lockedBlocks = [];
        const findLockedBlocks = (blockList) => {
            blockList.forEach(block => {
                if (block.attributes.paywallAnywhereLocked) {
                    lockedBlocks.push({
                        id: block.clientId,
                        name: block.name,
                        price: block.attributes.paywallAnywherePrice || 500,
                        currency: block.attributes.paywallAnywhereCurrency || 'USD',
                        expiry: block.attributes.paywallAnywhereExpiresDays || 30
                    });
                }
                if (block.innerBlocks && block.innerBlocks.length > 0) {
                    findLockedBlocks(block.innerBlocks);
                }
            });
        };
        
        findLockedBlocks(blocks);
        
        const unlockBlock = (blockId) => {
            updateBlockAttributes(blockId, {
                paywallAnywhereLocked: false
            });
        };
        
        const jumpToBlock = (blockId) => {
            const blockElement = document.querySelector(`[data-block="${blockId}"]`);
            if (blockElement) {
                blockElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                blockElement.style.border = '2px solid #667eea';
                setTimeout(() => {
                    blockElement.style.border = '';
                }, 2000);
            }
        };
        
        return wp.element.createElement('div', { className: 'paywall-anywhere-locked-elements' },
            lockedBlocks.length === 0 ? 
                wp.element.createElement('p', { style: { color: '#757575', fontStyle: 'italic' } },
                    __('No locked elements in this post.', 'paywall-anywhere')
                ) :
                wp.element.createElement('div', null,
                    wp.element.createElement('p', { style: { marginBottom: '16px', fontSize: '12px', color: '#757575' } },
                        lockedBlocks.length + ' ' + __('locked elements', 'paywall-anywhere')
                    ),
                    lockedBlocks.map((block, index) =>
                        wp.element.createElement('div', { 
                            key: block.id,
                            className: 'paywall-anywhere-locked-item',
                            style: { 
                                padding: '12px', 
                                border: '1px solid #ddd', 
                                borderRadius: '4px', 
                                marginBottom: '8px' 
                            }
                        },
                            wp.element.createElement('div', { 
                                style: { 
                                    display: 'flex', 
                                    justifyContent: 'space-between', 
                                    alignItems: 'center',
                                    marginBottom: '8px' 
                                }
                            },
                                wp.element.createElement('strong', null, 
                                    block.name.replace('core/', '').replace('paywall-anywhere/', '')
                                ),
                                wp.element.createElement('span', { style: { fontSize: '12px' } },
                                    '🔒'
                                )
                            ),
                            wp.element.createElement('div', { style: { fontSize: '12px', color: '#757575', marginBottom: '8px' } },
                                paywall_anywhere_format_price(block.price, block.currency) + 
                                ' • ' + block.expiry + ' ' + __('days', 'paywall-anywhere')
                            ),
                            wp.element.createElement('div', { style: { display: 'flex', gap: '8px' } },
                                wp.element.createElement(Button, {
                                    isSmall: true,
                                    onClick: () => jumpToBlock(block.id)
                                }, __('Jump to', 'paywall-anywhere')),
                                wp.element.createElement(Button, {
                                    isSmall: true,
                                    isDestructive: true,
                                    onClick: () => unlockBlock(block.id)
                                }, __('Unlock', 'paywall-anywhere'))
                            )
                        )
                    )
                )
        );
    };
    
    /**
     * Register the Locked Elements Panel
     */
    if (wp.plugins && wp.editPost) {
        const { registerPlugin } = wp.plugins;
        const { PluginDocumentSettingPanel } = wp.editPost;
        
        registerPlugin('paywall-anywhere-locked-elements', {
            render: () => {
                return wp.element.createElement(PluginDocumentSettingPanel, {
                    name: 'paywall-anywhere-locked-elements',
                    title: __('Paywall Anywhere — Locked Elements', 'paywall-anywhere'),
                    className: 'paywall-anywhere-locked-elements-panel'
                }, wp.element.createElement(LockedElementsPanel));
            },
            icon: 'lock'
        });
    }
    
    /**
     * Register Command Palette command for Find & Lock
     */
    if (wp.commands && wp.commandPalette) {
        const { registerCommand } = wp.commands;
        
        registerCommand({
            name: 'paywall-anywhere/find-and-lock',
            label: __('Find & Lock (Paywall Anywhere)', 'paywall-anywhere'),
            searchTerms: ['find', 'lock', 'paywall', 'premium'],
            callback: () => {
                // Focus on the Find & Lock panel if it exists
                const findLockPanel = document.querySelector('.paywall-anywhere-find-lock-panel');
                if (findLockPanel) {
                    findLockPanel.scrollIntoView({ behavior: 'smooth' });
                    const searchInput = findLockPanel.querySelector('input[type="text"]');
                    if (searchInput) {
                        searchInput.focus();
                    }
                }
            }
        });
    }
    
    /**
     * Add CSS styles for Find & Lock Control
     */
    if (document.head) {
        const style = document.createElement('style');
        style.textContent = `
            .paywall-anywhere-find-lock-control {
                padding: 16px 0;
            }
            
            .paywall-anywhere-search-header h4 {
                margin: 0 0 8px 0;
                font-size: 14px;
                font-weight: 600;
                color: #1e1e1e;
            }
            
            .paywall-anywhere-search-header p {
                margin: 0 0 16px 0;
                font-size: 12px;
                color: #757575;
            }
            
            .paywall-anywhere-search-box {
                margin-bottom: 16px;
            }
            
            .paywall-anywhere-search-loading {
                text-align: center;
                color: #757575;
                font-size: 12px;
                padding: 16px;
            }
            
            .paywall-anywhere-results-header {
                font-size: 12px;
                color: #757575;
                margin-bottom: 8px;
                padding-bottom: 8px;
                border-bottom: 1px solid #e0e0e0;
            }
            
            .paywall-anywhere-results-list {
                max-height: 300px;
                overflow-y: auto;
            }
            
            .paywall-anywhere-result-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px;
                border: 1px solid #e0e0e0;
                border-radius: 4px;
                margin-bottom: 8px;
                background: #f9f9f9;
                transition: all 0.2s ease;
            }
            
            .paywall-anywhere-result-item:hover {
                background: #f0f0f0;
                border-color: #667eea;
            }
            
            .paywall-anywhere-result-content {
                flex: 1;
                margin-right: 12px;
            }
            
            .paywall-anywhere-result-type {
                font-size: 11px;
                font-weight: 600;
                color: #667eea;
                text-transform: uppercase;
                margin-bottom: 4px;
            }
            
            .paywall-anywhere-result-text {
                font-size: 12px;
                color: #1e1e1e;
                line-height: 1.4;
            }
            
            .paywall-anywhere-result-actions {
                display: flex;
                gap: 8px;
            }
            
            .paywall-anywhere-result-actions .components-button {
                min-height: 30px;
                font-size: 11px;
            }
            
            .paywall-anywhere-no-results {
                text-align: center;
                color: #757575;
                font-size: 12px;
                padding: 24px;
                background: #f9f9f9;
                border-radius: 4px;
            }
            
            .paywall-anywhere-locked-overlay {
                position: absolute;
                top: 4px;
                right: 4px;
                background: rgba(102, 126, 234, 0.9);
                color: white;
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 10px;
                font-weight: 600;
                z-index: 10;
                display: flex;
                align-items: center;
                gap: 4px;
                pointer-events: none;
            }
            
            .paywall-anywhere-lock-icon {
                font-size: 10px;
            }
        `;
        document.head.appendChild(style);
    }
    
})(window.wp, jQuery);