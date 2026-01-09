/**
 * JavaScript Build Script for Ensemble Plugin
 * Minifies all JS files using Terser and outputs to dist/ folders
 */

const fs = require('fs');
const path = require('path');
const { minify } = require('terser');
const glob = require('glob');

// Konfiguration
const config = {
	// Haupt-Assets
	mainJs: [
		'assets/js/*.js'
	],
	// Admin JS
	adminJs: [
		'admin/js/*.js'
	],
	// Addon JS
	addonJs: [
		'includes/addons/*/assets/js/*.js'
	]
};

// Terser Optionen
const terserOptions = {
	compress: {
		drop_console: false, // console.log behalten für Debugging
		drop_debugger: true,
		dead_code: true,
		unused: true
	},
	mangle: {
		reserved: ['jQuery', '$', 'wp', 'ensemble_ajax', 'ensemble_admin', 'ensemble_vars']
	},
	format: {
		comments: false
	}
};

/**
 * Minifiziert eine JS-Datei
 */
async function minifyFile(inputPath) {
	const dir = path.dirname(inputPath);
	const basename = path.basename(inputPath, '.js');
	
	// Überspringe bereits minifizierte Dateien
	if (basename.endsWith('.min')) {
		return null;
	}
	
	// Erstelle dist Ordner wenn nötig
	const distDir = path.join(dir, 'dist');
	if (!fs.existsSync(distDir)) {
		fs.mkdirSync(distDir, { recursive: true });
	}
	
	const outputPath = path.join(distDir, `${basename}.min.js`);
	
	try {
		const input = fs.readFileSync(inputPath, 'utf8');
		const result = await minify(input, terserOptions);
		
		if (result.error) {
			console.error(`  ❌ Error in ${inputPath}:`, result.error);
			return null;
		}
		
		fs.writeFileSync(outputPath, result.code);
		
		const originalSize = Buffer.byteLength(input, 'utf8');
		const minifiedSize = Buffer.byteLength(result.code, 'utf8');
		const reduction = ((1 - minifiedSize / originalSize) * 100).toFixed(1);
		
		return {
			input: inputPath,
			output: outputPath,
			originalSize,
			minifiedSize,
			reduction
		};
	} catch (err) {
		console.error(`  ❌ Error processing ${inputPath}:`, err.message);
		return null;
	}
}

/**
 * Hauptfunktion
 */
async function build() {
	console.log('\n⚡ JavaScript Build gestartet...\n');
	
	const allPatterns = [
		...config.mainJs,
		...config.adminJs,
		...config.addonJs
	];
	
	let totalFiles = 0;
	let totalOriginal = 0;
	let totalMinified = 0;
	
	for (const pattern of allPatterns) {
		const files = glob.sync(pattern, { nodir: true });
		
		for (const file of files) {
			// Überspringe dist/ Ordner und .min.js Dateien
			if (file.includes('/dist/') || file.endsWith('.min.js')) {
				continue;
			}
			
			const result = await minifyFile(file);
			if (result) {
				totalFiles++;
				totalOriginal += result.originalSize;
				totalMinified += result.minifiedSize;
				console.log(`  ✓ ${result.input}`);
				console.log(`    → ${result.output} (-${result.reduction}%)`);
			}
		}
	}
	
	if (totalFiles > 0) {
		const totalReduction = ((1 - totalMinified / totalOriginal) * 100).toFixed(1);
		console.log('\n─────────────────────────────────────');
		console.log(`📊 Zusammenfassung:`);
		console.log(`   Dateien: ${totalFiles}`);
		console.log(`   Original: ${(totalOriginal / 1024).toFixed(1)} KB`);
		console.log(`   Minified: ${(totalMinified / 1024).toFixed(1)} KB`);
		console.log(`   Ersparnis: ${totalReduction}%`);
		console.log('─────────────────────────────────────\n');
	} else {
		console.log('  Keine JavaScript-Dateien gefunden.\n');
	}
}

// Script ausführen
build();
