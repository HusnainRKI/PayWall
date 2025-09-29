/**
 * PayWall Premium Content - Editor JavaScript
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
    registerBlockType('pc/gate-start', {
        title: __('Paywall: Gate Start', 'paywall-premium-content'),
        description: __('Mark the start of premium content. Everything below this block will be locked.', 'paywall-premium-content'),
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
            includeRoutes: {
                type: 'boolean',
                default: false
            }
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { price, currency, expiresDays, adFree, includeRoutes } = attributes;
            
            const blockProps = useBlockProps({
                className: 'pc-gate-start-block'
            });
            
            const priceDisplay = (price / 100).toFixed(2);
            
            return (
                <Fragment>
                    <InspectorControls>
                        <PanelBody title={__('Premium Content Settings', 'paywall-premium-content')}>
                            <TextControl
                                label={__('Price (cents)', 'paywall-premium-content')}
                                value={price}
                                onChange={(value) => setAttributes({ price: parseInt(value) || 0 })}
                                type="number"
                                min="0"
                                help={__('Price in cents (500 = $5.00)', 'paywall-premium-content')}
                            />
                            
                            <SelectControl
                                label={__('Currency', 'paywall-premium-content')}
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
                                label={__('Access Duration (Days)', 'paywall-premium-content')}
                                value={expiresDays}
                                onChange={(value) => setAttributes({ expiresDays: value })}
                                min={1}
                                max={365}
                                help={__('How long users have access after purchase', 'paywall-premium-content')}
                            />
                            
                            <ToggleControl
                                label={__('Ad-Free Mode', 'paywall-premium-content')}
                                checked={adFree}
                                onChange={(value) => setAttributes({ adFree: value })}
                                help={__('Hide ads for premium users', 'paywall-premium-content')}
                            />
                            
                            <ToggleControl
                                label={__('Include Print/PDF Routes', 'paywall-premium-content')}
                                checked={includeRoutes}
                                onChange={(value) => setAttributes({ includeRoutes: value })}
                                help={__('Apply paywall to print and PDF versions', 'paywall-premium-content')}
                            />
                        </PanelBody>
                    </InspectorControls>
                    
                    <div {...blockProps}>
                        <div className="pc-gate-start-preview">
                            <div className="pc-gate-icon">🚪</div>
                            <div className="pc-gate-content">
                                <h4>{__('Premium Content Gate', 'paywall-premium-content')}</h4>
                                <p>
                                    {__('Price:', 'paywall-premium-content')} <strong>{currency === 'USD' ? '$' : currency + ' '}{priceDisplay}</strong>
                                    {expiresDays && (
                                        <span> • {__('Access for', 'paywall-premium-content')} <strong>{expiresDays} {__('days', 'paywall-premium-content')}</strong></span>
                                    )}
                                </p>
                                <p className="pc-gate-description">
                                    {__('All content below this block will be locked for non-premium users.', 'paywall-premium-content')}
                                </p>
                            </div>
                        </div>
                    </div>
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
    registerBlockType('pc/unlock-cta', {
        title: __('Paywall: Unlock CTA', 'paywall-premium-content'),
        description: __('Display a call-to-action button for purchasing premium content.', 'paywall-premium-content'),
        icon: 'unlock',
        category: 'widgets',
        keywords: [__('paywall'), __('unlock'), __('purchase'), __('cta')],
        attributes: {
            itemId: {
                type: 'number',
                default: 0
            },
            style: {
                type: 'string',
                default: 'filled'
            },
            customText: {
                type: 'string',
                default: ''
            },
            alignment: {
                type: 'string',
                default: 'center'
            }
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { itemId, style, customText, alignment } = attributes;
            
            const [availableItems, setAvailableItems] = useState([]);
            const [loading, setLoading] = useState(false);
            
            const blockProps = useBlockProps({
                className: `pc-unlock-cta-block pc-align-${alignment}`
            });
            
            // Load available premium items for current post
            const postId = useSelect(select => select('core/editor').getCurrentPostId());
            
            React.useEffect(() => {
                if (postId) {
                    setLoading(true);
                    wp.apiFetch({
                        path: `/pc/v1/items?post=${postId}`
                    }).then(items => {
                        setAvailableItems(items);
                        setLoading(false);
                    }).catch(() => {
                        setLoading(false);
                    });
                }
            }, [postId]);
            
            const selectedItem = availableItems.find(item => item.id === itemId);
            
            return (
                <Fragment>
                    <InspectorControls>
                        <PanelBody title={__('Unlock Button Settings', 'paywall-premium-content')}>
                            <SelectControl
                                label={__('Premium Item', 'paywall-premium-content')}
                                value={itemId}
                                options={[
                                    { label: __('Select an item...', 'paywall-premium-content'), value: 0 },
                                    ...availableItems.map(item => ({
                                        label: `${item.scope}: ${item.selector || 'Whole Post'} - $${(item.price_minor / 100).toFixed(2)}`,
                                        value: item.id
                                    }))
                                ]}
                                onChange={(value) => setAttributes({ itemId: parseInt(value) })}
                                help={loading ? __('Loading items...', 'paywall-premium-content') : __('Choose which premium item this button unlocks', 'paywall-premium-content')}
                            />
                            
                            <SelectControl
                                label={__('Button Style', 'paywall-premium-content')}
                                value={style}
                                options={[
                                    { label: __('Filled', 'paywall-premium-content'), value: 'filled' },
                                    { label: __('Outline', 'paywall-premium-content'), value: 'outline' }
                                ]}
                                onChange={(value) => setAttributes({ style: value })}
                            />
                            
                            <SelectControl
                                label={__('Alignment', 'paywall-premium-content')}
                                value={alignment}
                                options={[
                                    { label: __('Left', 'paywall-premium-content'), value: 'left' },
                                    { label: __('Center', 'paywall-premium-content'), value: 'center' },
                                    { label: __('Right', 'paywall-premium-content'), value: 'right' }
                                ]}
                                onChange={(value) => setAttributes({ alignment: value })}
                            />
                            
                            <TextControl
                                label={__('Custom Button Text', 'paywall-premium-content')}
                                value={customText}
                                onChange={(value) => setAttributes({ customText: value })}
                                help={__('Leave empty to use default text', 'paywall-premium-content')}
                            />
                        </PanelBody>
                    </InspectorControls>
                    
                    <div {...blockProps}>
                        <div className={`pc-unlock-cta-preview pc-unlock-cta--${style}`}>
                            {selectedItem ? (
                                <div className="pc-cta-content">
                                    <h4>{__('Premium Content Available', 'paywall-premium-content')}</h4>
                                    <p>{__('Unlock this content for', 'paywall-premium-content')} <strong>${(selectedItem.price_minor / 100).toFixed(2)}</strong></p>
                                    <button className={`pc-unlock-btn pc-unlock-btn--${style}`}>
                                        {customText || `${__('Unlock for', 'paywall-premium-content')} $${(selectedItem.price_minor / 100).toFixed(2)}`}
                                    </button>
                                </div>
                            ) : (
                                <div className="pc-cta-placeholder">
                                    <p>{__('Select a premium item to configure this unlock button.', 'paywall-premium-content')}</p>
                                </div>
                            )}
                        </div>
                    </div>
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
            const isLocked = attributes.pcLocked || false;
            const price = attributes.pcPrice || 500;
            const currency = attributes.pcCurrency || 'USD';
            const expiresDays = attributes.pcExpiresDays || 30;
            
            return (
                <Fragment>
                    <BlockEdit {...props} />
                    <InspectorControls>
                        <PanelBody title={__('PayWall Settings', 'paywall-premium-content')} initialOpen={false}>
                            <ToggleControl
                                label={__('Lock this block', 'paywall-premium-content')}
                                checked={isLocked}
                                onChange={(value) => {
                                    setAttributes({ 
                                        pcLocked: value,
                                        pcPrice: value ? price : undefined,
                                        pcCurrency: value ? currency : undefined,
                                        pcExpiresDays: value ? expiresDays : undefined
                                    });
                                }}
                                help={__('Make this block premium content', 'paywall-premium-content')}
                            />
                            
                            {isLocked && (
                                <Fragment>
                                    <TextControl
                                        label={__('Price (cents)', 'paywall-premium-content')}
                                        value={price}
                                        onChange={(value) => setAttributes({ pcPrice: parseInt(value) || 0 })}
                                        type="number"
                                        min="0"
                                    />
                                    
                                    <SelectControl
                                        label={__('Currency', 'paywall-premium-content')}
                                        value={currency}
                                        options={[
                                            { label: 'USD ($)', value: 'USD' },
                                            { label: 'EUR (€)', value: 'EUR' },
                                            { label: 'GBP (£)', value: 'GBP' },
                                            { label: 'JPY (¥)', value: 'JPY' }
                                        ]}
                                        onChange={(value) => setAttributes({ pcCurrency: value })}
                                    />
                                    
                                    <RangeControl
                                        label={__('Access Duration (Days)', 'paywall-premium-content')}
                                        value={expiresDays}
                                        onChange={(value) => setAttributes({ pcExpiresDays: value })}
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
    
    addFilter('editor.BlockEdit', 'pc/with-lock-controls', withLockControls);
    
    /**
     * Add locked indicator to blocks
     */
    const withLockedIndicator = createHigherOrderComponent((BlockListBlock) => {
        return (props) => {
            const { attributes } = props;
            const isLocked = attributes.pcLocked || false;
            
            if (!isLocked) {
                return <BlockListBlock {...props} />;
            }
            
            return (
                <div className="pc-locked-block-wrapper">
                    <div className="pc-locked-indicator">
                        🔒 {__('Premium Content', 'paywall-premium-content')} - ${((attributes.pcPrice || 500) / 100).toFixed(2)}
                    </div>
                    <BlockListBlock {...props} />
                </div>
            );
        };
    }, 'withLockedIndicator');
    
    addFilter('editor.BlockListBlock', 'pc/with-locked-indicator', withLockedIndicator);
    
})(window.wp, jQuery);