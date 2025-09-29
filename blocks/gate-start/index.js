/**
 * Gate Start Block
 */

import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, SelectControl, RangeControl, ToggleControl } from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

registerBlockType('pc/gate-start', {
    edit: ({ attributes, setAttributes }) => {
        const { price, currency, expiresDays, adFree, includeRoutes } = attributes;
        
        const blockProps = useBlockProps({
            className: 'pc-gate-start-block'
        });
        
        const priceDisplay = (price / 100).toFixed(2);
        const currencySymbol = currency === 'USD' ? '$' : currency + ' ';
        
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
                                {__('Price:', 'paywall-premium-content')} <strong>{currencySymbol}{priceDisplay}</strong>
                                {expiresDays > 0 && (
                                    <span> • {__('Access for', 'paywall-premium-content')} <strong>{expiresDays} {__('days', 'paywall-premium-content')}</strong></span>
                                )}
                            </p>
                            <p className="pc-gate-description">
                                {__('All content below this block will be locked for non-premium users.', 'paywall-premium-content')}
                            </p>
                            {adFree && (
                                <p className="pc-gate-feature">
                                    ✨ {__('Ad-free experience included', 'paywall-premium-content')}
                                </p>
                            )}
                        </div>
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