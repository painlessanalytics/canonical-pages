const {
	data: { useSelect, useDispatch },
	plugins: { registerPlugin },
	element: { useState, useEffect },
	components: { TextControl, ToggleControl, RadioControl },
	editor: { PluginDocumentSettingPanel },
    i18n: { __ },
} = wp;

/**
 * Sidebar Settings
 */
const canonicalPagesSettings = () => {

	const meta = useSelect(function (select) {
        const data = select('core/editor').getEditedPostAttribute('meta');
        return data;
	}, []);

    const enableCanonicalPages = meta && meta['_canonical_pages'] ? meta['_canonical_pages'] : true;
    const canonicalPagesOption = meta && meta['_canonical_pages_meta'] && meta['_canonical_pages_meta'].option ? meta['_canonical_pages_meta'].option : 'this';
    const canonicalPagesUrl = meta && meta['_canonical_pages_meta'] && meta['_canonical_pages_meta'].url ? meta['_canonical_pages_meta'].url : '';

    const [canonicalEnabled, setCanonicalEnabled] = useState( enableCanonicalPages );
    const [canonicalOption, setCanonicalOption] = useState( canonicalPagesOption );
    const [canonicalUrl, setCanonicalUrl] = useState(canonicalPagesUrl);

	const { editPost } = useDispatch('core/editor');

	useEffect(() => {
		editPost({
			meta: {
				_canonical_pages: canonicalEnabled,
                _canonical_pages_meta: {
                    option: canonicalOption,
                    url: canonicalUrl
                }
			},
		});
	}, [canonicalEnabled, canonicalOption, canonicalUrl]);

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
                </>
            )}
		</PluginDocumentSettingPanel>
	);
};


registerPlugin('canonical-pages', {
    render: canonicalPagesSettings,
    icon: null,
});
