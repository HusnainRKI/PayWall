/**
 * Unlock CTA Block
 */

import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { Fragment, useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

registerBlockType('pc/unlock-cta', {
    edit: ({ attributes, setAttributes }) => {
        const { itemId, style, customText, alignment } = attributes;
        
        const [availableItems, setAvailableItems] = useState([]);
        const [loading, setLoading] = useState(false);
        
        const blockProps = useBlockProps({
            className: `pc-unlock-cta-block pc-align-${alignment}`
        });
        
        // Get current post ID
        const postId = useSelect(select => select('core/editor').getCurrentPostId());
        
        // Load available premium items for current post
        useEffect(() => {
            if (postId) {
                setLoading(true);
                apiFetch({
                    path: `/pc/v1/items?post=${postId}`
                }).then(items => {
                    setAvailableItems(items || []);
                    setLoading(false);
                }).catch(() => {
                    setAvailableItems([]);
                    setLoading(false);
                });
            }
        }, [postId]);
        
        const selectedItem = availableItems.find(item => item.id === itemId);
        
        const getDefaultButtonText = (item) => {
            if (!item) return __('Unlock Premium Content', 'paywall-premium-content');
            
            const price = (item.price_minor / 100).toFixed(2);
            const symbol = item.currency === 'USD' ? '$' : item.currency + ' ';
            return `${__('Unlock for', 'paywall-premium-content')} ${symbol}${price}`;
        };
        
        return (
            <Fragment>
                <InspectorControls>
                    <PanelBody title={__('Unlock Button Settings', 'paywall-premium-content')}>
                        <SelectControl
                            label={__('Premium Item', 'paywall-premium-content')}
                            value={itemId}
                            options={[
                                { label: __('Select an item...', 'paywall-premium-content'), value: 0 },
                                ...availableItems.map(item => {
                                    const price = (item.price_minor / 100).toFixed(2);
                                    const symbol = item.currency === 'USD' ? '$' : item.currency + ' ';
                                    const label = `${item.scope}: ${item.selector || 'Whole Post'} - ${symbol}${price}`;
                                    return {
                                        label,
                                        value: item.id
                                    };
                                })
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
                                <p>
                                    {__('Unlock this content for', 'paywall-premium-content')} <strong>${(selectedItem.price_minor / 100).toFixed(2)}</strong>
                                    {selectedItem.expires_days && (
                                        <span> • {__('Access for', 'paywall-premium-content')} <strong>{selectedItem.expires_days} {__('days', 'paywall-premium-content')}</strong></span>
                                    )}
                                </p>
                                <button className={`pc-unlock-btn pc-unlock-btn--${style}`}>
                                    {customText || getDefaultButtonText(selectedItem)}
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
    
    save: () => {
        // This is a dynamic block, so we return null and let PHP handle the rendering
        return null;
    }
});