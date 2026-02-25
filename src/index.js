import { addFilter } from '@wordpress/hooks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, Notice } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

const SUPPORTED_BLOCKS = [ 'core/heading', 'core/post-title' ];
const ATTR_NAME        = 'nextCharLimit';

// -------------------------------------------------------
// 1. 対象ブロックに nextCharLimit 属性を追加
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
				[ ATTR_NAME ]: {
					type:    'number',
					default: 0,
				},
			},
		};
	}
);

// -------------------------------------------------------
// 2. BlockEdit に文字数制限パネルを追加
// -------------------------------------------------------

/** HTML からプレーンテキストを取得 */
const getPlainText = ( html ) => {
	if ( ! html ) return '';
	const el = document.createElement( 'div' );
	el.innerHTML = html;
	return el.textContent || el.innerText || '';
};

/** 文字数制限パネルの共通 UI（core/heading 用） */
const CharLimitPanel = ( { charLimit, charCount, setAttributes } ) => {
	const isOver = charLimit > 0 && charCount > charLimit;

	const handleChange = ( value ) => {
		const parsed = parseInt( value, 10 );
		setAttributes( { [ ATTR_NAME ]: isNaN( parsed ) || parsed < 0 ? 0 : parsed } );
	};

	return (
		<InspectorControls>
			<PanelBody
				title={ __( '文字数制限', 'next-title-trim-count' ) }
				initialOpen={ true }
			>
				<TextControl
					label={ __( '最大文字数', 'next-title-trim-count' ) }
					help={ __( '0 を設定すると制限なし', 'next-title-trim-count' ) }
					type="number"
					min={ 0 }
					value={ charLimit === 0 ? '' : String( charLimit ) }
					placeholder="0"
					onChange={ handleChange }
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
			</PanelBody>
		</InspectorControls>
	);
};

/**
 * core/post-title 用パネル
 *
 * クエリーループ内では各投稿のタイトルが動的に変わるため文字数の表示はせず、
 * 制限値の設定とフロントエンドでの動作説明のみを表示する。
 */
const PostTitleCharLimitPanel = ( { charLimit, setAttributes } ) => {
	// クエリーループ内かどうかを判定
	// （useBlockEditContext ではなく親コンテキストで確認）
	const isInsideQueryLoop = useSelect( ( select ) => {
		const { getBlockParentsByBlockName, getSelectedBlockClientId } =
			select( 'core/block-editor' );
		const clientId = getSelectedBlockClientId();
		if ( ! clientId ) return false;
		const queryParents = getBlockParentsByBlockName( clientId, 'core/query' );
		return queryParents.length > 0;
	}, [] );

	const handleChange = ( value ) => {
		const parsed = parseInt( value, 10 );
		setAttributes( { [ ATTR_NAME ]: isNaN( parsed ) || parsed < 0 ? 0 : parsed } );
	};

	return (
		<InspectorControls>
			<PanelBody
				title={ __( '文字数制限', 'next-title-trim-count' ) }
				initialOpen={ true }
			>
				<TextControl
					label={ __( '最大文字数', 'next-title-trim-count' ) }
					help={ __( '0 を設定すると制限なし', 'next-title-trim-count' ) }
					type="number"
					min={ 0 }
					value={ charLimit === 0 ? '' : String( charLimit ) }
					placeholder="0"
					onChange={ handleChange }
				/>

				{ isInsideQueryLoop && charLimit > 0 && (
					<Notice status="info" isDismissible={ false }>
						{ __( 'クエリーループ内の全投稿タイトルに適用されます。省略はフロントエンドのみで確認できます。', 'next-title-trim-count' ) }
					</Notice>
				) }
			</PanelBody>
		</InspectorControls>
	);
};

const withCharLimitControl = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		if ( ! SUPPORTED_BLOCKS.includes( props.name ) ) {
			return <BlockEdit { ...props } />;
		}

		const { attributes, setAttributes, name } = props;
		const charLimit = attributes[ ATTR_NAME ] || 0;

		// core/post-title は動的コンテンツのため専用パネルを使用
		if ( name === 'core/post-title' ) {
			return (
				<>
					<BlockEdit { ...props } />
					<PostTitleCharLimitPanel
						charLimit={ charLimit }
						setAttributes={ setAttributes }
					/>
				</>
			);
		}

		// core/heading はブロック属性 content から文字数を算出
		// スプレッド構文で Unicode コードポイント単位にカウント（絵文字等のサロゲートペア対応）
		const charCount = [ ...getPlainText( attributes.content || '' ) ].length;

		return (
			<>
				<BlockEdit { ...props } />
				<CharLimitPanel
					charLimit={ charLimit }
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
