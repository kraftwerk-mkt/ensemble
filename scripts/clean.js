/**
 * Clean Script for Ensemble Plugin
 * Removes all dist/ folders and .min.* files before rebuild
 */

const fs = require('fs');
const path = require('path');
const glob = require('glob');

console.log('\n🧹 Cleaning dist folders...\n');

// Finde alle dist Ordner
const distFolders = glob.sync('**/dist/', {
	ignore: ['node_modules/**', 'vendor/**']
});

let cleaned = 0;

distFolders.forEach(folder => {
	try {
		fs.rmSync(folder, { recursive: true, force: true });
		console.log(`  ✓ Removed: ${folder}`);
		cleaned++;
	} catch (err) {
		console.error(`  ❌ Error removing ${folder}:`, err.message);
	}
});

if (cleaned > 0) {
	console.log(`\n  ${cleaned} dist folder(s) removed.\n`);
} else {
	console.log('  No dist folders found.\n');
}
