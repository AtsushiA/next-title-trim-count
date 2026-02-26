import { addFilter } from '@wordpress/hooks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, RadioControl, Notice } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

const SUPPORTED_BLOCKS = [ 'core/heading', 'core/post-title' ];
const ATTR_CHAR        = 'nextCharLimit';
const ATTR_LINE        = 'nextLineClamp';

// -------------------------------------------------------
// 1. 対象ブロックに属性を追加
// -------------------------------------------------------
addFilter(
	'blocks.registerBlockType',
	'next/title-trim-count/add-attribute',
	( settings, name ) => {
		if ( ! SUPPORTED_BLOCKS.includes( name ) ) {
			return settings;
		}
		return {
			...settings,
			attributes: {
				...( settings.attributes || {} ),
				[ ATTR_CHAR ]: { type: 'number', default: 0 },
				[ ATTR_LINE ]: { type: 'number', default: 0 },
			},
		};
	}
);

// -------------------------------------------------------
// ユーティリティ
// -------------------------------------------------------

/** HTML からプレーンテキストを取得 */
const getPlainText = ( html ) => {
	if ( ! html ) return '';
	const el = document.createElement( 'div' );
	el.innerHTML = html;
	return el.textContent || el.innerText || '';
};

/**
 * 現在の制限タイプを返す
 * nextLineClamp > 0 なら 'line'、それ以外は 'char'
 */
const getTrimMode = ( lineClamp ) =>
	lineClamp > 0 ? 'line' : 'char';

/** 数値入力コントロール（共通） */
const LimitInput = ( { label, help, value, onChange } ) => (
	<TextControl
		label={ label }
		help={ help }
		type="number"
		min={ 0 }
		value={ value === 0 ? '' : String( value ) }
		placeholder="0"
		onChange={ ( v ) => {
			const parsed = parseInt( v, 10 );
			onChange( isNaN( parsed ) || parsed < 0 ? 0 : parsed );
		} }
	/>
);

// -------------------------------------------------------
// 2. core/heading 用パネル
// -------------------------------------------------------
const CharLimitPanel = ( { charLimit, lineClamp, charCount, setAttributes } ) => {
	const trimMode = getTrimMode( lineClamp );
	const isOver   = trimMode === 'char' && charLimit > 0 && charCount > charLimit;

	const handleModeChange = ( mode ) => {
		if ( mode === 'line' ) {
			setAttributes( { [ ATTR_CHAR ]: 0 } );
		} else {
			setAttributes( { [ ATTR_LINE ]: 0 } );
		}
	};

	return (
		<InspectorControls>
			<PanelBody
				title={ __( '文字数制限', 'next-title-trim-count' ) }
				initialOpen={ true }
			>
				<RadioControl
					label={ __( '制限タイプ', 'next-title-trim-count' ) }
					selected={ trimMode }
					options={ [
						{ label: __( '文字数', 'next-title-trim-count' ), value: 'char' },
						{ label: __( '行数', 'next-title-trim-count' ),   value: 'line' },
					] }
					onChange={ handleModeChange }
				/>

				{ trimMode === 'char' && (
					<>
						<LimitInput
							label={ __( '最大文字数', 'next-title-trim-count' ) }
							help={ __( '0 を設定すると制限なし', 'next-title-trim-count' ) }
							value={ charLimit }
							onChange={ ( v ) => setAttributes( { [ ATTR_CHAR ]: v } ) }
						/>

						{ charLimit > 0 && (
							<p style={ { marginTop: '8px', fontSize: '12px' } }>
								{ __( '現在の文字数:', 'next-title-trim-count' ) }{ ' ' }
								<strong>{ charCount }</strong>{ ' ' }
								{ __( '文字', 'next-title-trim-count' ) }
							</p>
						) }

						{ isOver && (
							<Notice status="warning" isDismissible={ false }>
								{ charCount - charLimit }{ ' ' }
								{ __( '文字超過 — フロントエンドで「…」に省略されます', 'next-title-trim-count' ) }
							</Notice>
						) }
					</>
				) }

				{ trimMode === 'line' && (
					<>
						<LimitInput
							label={ __( '最大行数', 'next-title-trim-count' ) }
							help={ __( '0 を設定すると制限なし', 'next-title-trim-count' ) }
							value={ lineClamp }
							onChange={ ( v ) => setAttributes( { [ ATTR_LINE ]: v } ) }
						/>

						{ lineClamp > 0 && (
							<Notice status="info" isDismissible={ false }>
								{ __( '行数はブラウザの表示幅・フォントサイズに依存します。省略はフロントエンドのみで確認できます。', 'next-title-trim-count' ) }
							</Notice>
						) }
					</>
				) }
			</PanelBody>
		</InspectorControls>
	);
};

// -------------------------------------------------------
// 3. core/post-title 用パネル
// -------------------------------------------------------
const PostTitleCharLimitPanel = ( { charLimit, lineClamp, setAttributes } ) => {
	const isInsideQueryLoop = useSelect( ( select ) => {
		const { getBlockParentsByBlockName, getSelectedBlockClientId } =
			select( 'core/block-editor' );
		const clientId = getSelectedBlockClientId();
		if ( ! clientId ) return false;
		return getBlockParentsByBlockName( clientId, 'core/query' ).length > 0;
	}, [] );

	const trimMode = getTrimMode( lineClamp );

	const handleModeChange = ( mode ) => {
		if ( mode === 'line' ) {
			setAttributes( { [ ATTR_CHAR ]: 0 } );
		} else {
			setAttributes( { [ ATTR_LINE ]: 0 } );
		}
	};

	return (
		<InspectorControls>
			<PanelBody
				title={ __( '文字数制限', 'next-title-trim-count' ) }
				initialOpen={ true }
			>
				<RadioControl
					label={ __( '制限タイプ', 'next-title-trim-count' ) }
					selected={ trimMode }
					options={ [
						{ label: __( '文字数', 'next-title-trim-count' ), value: 'char' },
						{ label: __( '行数', 'next-title-trim-count' ),   value: 'line' },
					] }
					onChange={ handleModeChange }
				/>

				{ trimMode === 'char' && (
					<LimitInput
						label={ __( '最大文字数', 'next-title-trim-count' ) }
						help={ __( '0 を設定すると制限なし', 'next-title-trim-count' ) }
						value={ charLimit }
						onChange={ ( v ) => setAttributes( { [ ATTR_CHAR ]: v } ) }
					/>
				) }

				{ trimMode === 'line' && (
					<LimitInput
						label={ __( '最大行数', 'next-title-trim-count' ) }
						help={ __( '0 を設定すると制限なし', 'next-title-trim-count' ) }
						value={ lineClamp }
						onChange={ ( v ) => setAttributes( { [ ATTR_LINE ]: v } ) }
					/>
				) }

				{ isInsideQueryLoop && ( charLimit > 0 || lineClamp > 0 ) && (
					<Notice status="info" isDismissible={ false }>
						{ __( 'クエリーループ内の全投稿タイトルに適用されます。省略はフロントエンドのみで確認できます。', 'next-title-trim-count' ) }
					</Notice>
				) }
			</PanelBody>
		</InspectorControls>
	);
};

// -------------------------------------------------------
// 4. BlockEdit HOC
// -------------------------------------------------------
const withCharLimitControl = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		if ( ! SUPPORTED_BLOCKS.includes( props.name ) ) {
			return <BlockEdit { ...props } />;
		}

		const { attributes, setAttributes, name } = props;
		const charLimit = attributes[ ATTR_CHAR ] || 0;
		const lineClamp = attributes[ ATTR_LINE ] || 0;

		if ( name === 'core/post-title' ) {
			return (
				<>
					<BlockEdit { ...props } />
					<PostTitleCharLimitPanel
						charLimit={ charLimit }
						lineClamp={ lineClamp }
						setAttributes={ setAttributes }
					/>
				</>
			);
		}

		// core/heading: スプレッド構文で Unicode コードポイント単位にカウント
		const charCount = [ ...getPlainText( attributes.content || '' ) ].length;

		return (
			<>
				<BlockEdit { ...props } />
				<CharLimitPanel
					charLimit={ charLimit }
					lineClamp={ lineClamp }
					charCount={ charCount }
					setAttributes={ setAttributes }
				/>
			</>
		);
	};
}, 'withCharLimitControl' );

addFilter(
	'editor.BlockEdit',
	'next/title-trim-count/with-inspector',
	withCharLimitControl
);
