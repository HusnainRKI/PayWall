/**
 * Paywall Anywhere - Editor JavaScript
 */

(function(wp, $) {
    'use strict';
    
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, TextControl, SelectControl, ToggleControl, RangeControl, Button } = wp.components;
    const { Fragment, useState } = wp.element;
    const { __ } = wp.i18n;
    const { useSelect, useDispatch } = wp.data;
    
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
    const { createHigherOrderComponent } = wp.compose;
    const { addFilter } = wp.hooks;
    
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
                        <PanelBody title={__('Paywall Settings', 'paywall-anywhere')} initialOpen={false}>
                            <ToggleControl
                                label={__('Lock this block', 'paywall-anywhere')}
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
    
})(window.wp, jQuery);