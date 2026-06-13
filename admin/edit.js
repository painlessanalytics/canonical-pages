const {
	data: { useSelect, useDispatch },
	plugins: { registerPlugin },
	element: { useState, useEffect },
	components: { TextControl, ToggleControl, RadioControl, SelectControl },
	editor: { PluginDocumentSettingPanel },
    i18n: { __ },
} = wp;

const canonicalPagesData = window.canonicalPagesData || {};

/**
 * Sidebar Settings
 */
const canonicalPagesSettings = () => {

	const meta = useSelect(function (select) {
        const data = select('core/editor').getEditedPostAttribute('meta');
        return data;
	}, []);

    // Published "Canonical UTM Sources" records (only needed when the feature is on)
    const utmSources = useSelect(function (select) {
        if ( ! canonicalPagesData.utmVariantsEnabled ) {
            return [];
        }
        const records = select('core').getEntityRecords('postType', canonicalPagesData.utmCpt, {
            status: 'publish',
            per_page: 100,
            orderby: 'title',
            order: 'asc',
        });
        return records || [];
    }, []);

    const enableCanonicalPages = meta && meta['_canonical_pages'] ? meta['_canonical_pages'] : true;
    const canonicalPagesOption = meta && meta['_canonical_pages_meta'] && meta['_canonical_pages_meta'].option ? meta['_canonical_pages_meta'].option : 'this';
    const canonicalPagesUrl = meta && meta['_canonical_pages_meta'] && meta['_canonical_pages_meta'].url ? meta['_canonical_pages_meta'].url : '';
    const canonicalPagesVariant = meta && meta['_canonical_pages_meta'] && meta['_canonical_pages_meta'].variant ? meta['_canonical_pages_meta'].variant : 0;

    const [canonicalEnabled, setCanonicalEnabled] = useState( enableCanonicalPages );
    const [canonicalOption, setCanonicalOption] = useState( canonicalPagesOption );
    const [canonicalUrl, setCanonicalUrl] = useState(canonicalPagesUrl);
    const [canonicalVariant, setCanonicalVariant] = useState(canonicalPagesVariant);

	const { editPost } = useDispatch('core/editor');

	useEffect(() => {
		editPost({
			meta: {
				_canonical_pages: canonicalEnabled,
                _canonical_pages_meta: {
                    option: canonicalOption,
                    url: canonicalUrl,
                    variant: parseInt(canonicalVariant, 10) || 0
                }
			},
		});
	}, [canonicalEnabled, canonicalOption, canonicalUrl, canonicalVariant]);

    const variantOptions = [
        { label: __("— None —", 'canonical-pages'), value: 0 }
    ].concat(
        utmSources.map(function (record) {
            return {
                label: record.title && record.title.rendered ? record.title.rendered : ('#' + record.id),
                value: record.id
            };
        })
    );

	return (
		<PluginDocumentSettingPanel name="canonical-pages-settings" title={ __("Canonical",'canonical-pages') }>
            <div style={{ marginBottom: '20px' }}>
			<ToggleControl
				label={
                    canonicalEnabled
						? __("Enabled",'canonical-pages')
						: __("Disabled",'canonical-pages')
                }
				checked={ canonicalEnabled }
				onChange={ setCanonicalEnabled }
                __nextHasNoMarginBottom
			/>
            </div>
            {canonicalEnabled && (
                <>
                <RadioControl
                    label={ __("URL",'canonical-pages') }
                    onChange={ setCanonicalOption }
                    options={[
                        {
                        label: __("This Link",'canonical-pages'),
                        value: 'this'
                        },
                        {
                        label: __("Custom",'canonical-pages'),
                        value: 'custom'
                        }
                    ]}
                    selected={ canonicalOption }
                    />
                {canonicalOption == 'custom' && (
                    <div style={{ marginTop: '10px', paddingTop: '10px' }}>
                    <TextControl type="url" placeholder="https://example.com" value={canonicalUrl} onChange={setCanonicalUrl} />
                    </div>
                )}
                {canonicalOption == 'this' && canonicalPagesData.utmVariantsEnabled && (
                    <div style={{ marginTop: '10px', paddingTop: '10px' }}>
                    <SelectControl
                        label={ __("Variant UTM List",'canonical-pages') }
                        help={ __("Allow this page to also load at additional paths defined by the selected UTM Source list.",'canonical-pages') }
                        value={ canonicalVariant }
                        options={ variantOptions }
                        onChange={ setCanonicalVariant }
                        __nextHasNoMarginBottom
                    />
                    </div>
                )}
                </>
            )}
		</PluginDocumentSettingPanel>
	);
};


registerPlugin('canonical-pages', {
    render: canonicalPagesSettings,
    icon: null,
});
