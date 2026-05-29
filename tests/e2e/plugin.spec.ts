import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Next Title TrimCount', () => {
	test( 'プラグインが有効化されている', async ( { admin, page } ) => {
		await admin.visitAdminPage( 'plugins.php' );
		const pluginRow = page.locator( 'tr[data-slug="next-title-trim-count"]' );
		await expect( pluginRow ).toHaveClass( /active/ );
	} );

	test( '見出しブロックにサイドバーパネルが表示される', async ( { admin, editor, page } ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: 'core/heading' } );
		await page.keyboard.type( 'テスト見出し' );

		// 見出しブロックを選択してインスペクターを開く
		await page.click( '[data-type="core/heading"]' );
		const settingsButton = page.locator( 'button[aria-label="Settings"]' );
		if ( await settingsButton.isVisible() ) {
			await settingsButton.click();
		}

		// 文字数制限パネルの存在確認
		const panel = page.locator( '.components-panel__body', { hasText: '文字数制限' } );
		await expect( panel ).toBeVisible();
	} );
} );
