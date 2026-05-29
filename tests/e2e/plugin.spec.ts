import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Next Title TrimCount', () => {
	test( 'プラグインが有効化されている', async ( { admin, page } ) => {
		await admin.visitAdminPage( 'plugins.php' );
		// WordPress が data-slug を小文字化するため lowercase で指定
		const pluginRow = page.locator( 'tr[data-slug="next-title-trimcount"]' );
		await expect( pluginRow ).toHaveClass( /active/ );
	} );

	test( '見出しブロックにサイドバーパネルが表示される', async ( { admin, editor, page } ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: 'core/heading' } );
		await page.keyboard.type( 'テスト見出し' );

		// ブロックが挿入・フォーカスされた状態でサイドバーを開く
		const settingsButton = page.locator(
			'button[aria-label="Settings"], button[aria-label="設定"]'
		).first();
		const isSettingsVisible = await settingsButton.isVisible().catch( () => false );
		if ( isSettingsVisible ) {
			await settingsButton.click();
		}

		// 文字数制限パネルの存在確認（見出しブロック選択状態で表示される）
		const panel = page.locator( '.components-panel__body' ).filter( { hasText: '文字数制限' } );
		await expect( panel ).toBeVisible( { timeout: 10000 } );
	} );
} );
