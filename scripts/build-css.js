/**
 * CSS Build Script for Ensemble Plugin
 * Minifies all CSS files and outputs to dist/ folders
 */

const fs = require('fs');
const path = require('path');
const CleanCSS = require('clean-css');
const glob = require('glob');

// Konfiguration
const config = {
	// Haupt-Assets
	mainCss: [
		'assets/css/*.css'
	],
	// Admin CSS
	adminCss: [
		'admin/css/*.css'
	],
	// Layout CSS (in Unterordnern)
	layoutCss: [
		'assets/css/layouts/*/*.css'
	],
	// Addon CSS
	addonCss: [
		'includes/addons/*/assets/css/*.css'
	]
};

// CleanCSS Optionen
const cleanCssOptions = {
	level: {
		1: {
			specialComments: 'none'
		},
		2: {
			mergeMedia: true,
			removeEmpty: true,
			removeDuplicateFontRules: true,
			removeDuplicateMediaBlocks: true,
			removeDuplicateRules: true
		}
	},
	sourceMap: false
};

/**
 * Minifiziert eine CSS-Datei
 */
function minifyFile(inputPath) {
	const dir = path.dirname(inputPath);
	const basename = path.basename(inputPath, '.css');
	
	// Überspringe bereits minifizierte Dateien
	if (basename.endsWith('.min')) {
		return null;
	}
	
	// Erstelle dist Ordner wenn nötig
	const distDir = path.join(dir, 'dist');
	if (!fs.existsSync(distDir)) {
		fs.mkdirSync(distDir, { recursive: true });
	}
	
	const outputPath = path.join(distDir, `${basename}.min.css`);
	
	try {
		const input = fs.readFileSync(inputPath, 'utf8');
		const output = new CleanCSS(cleanCssOptions).minify(input);
		
		if (output.errors.length > 0) {
			console.error(`  ❌ Errors in ${inputPath}:`, output.errors);
			return null;
		}
		
		fs.writeFileSync(outputPath, output.styles);
		
		const originalSize = Buffer.byteLength(input, 'utf8');
		const minifiedSize = Buffer.byteLength(output.styles, 'utf8');
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
function build() {
	console.log('\n🎨 CSS Build gestartet...\n');
	
	const allPatterns = [
		...config.mainCss,
		...config.adminCss,
		...config.layoutCss,
		...config.addonCss
	];
	
	let totalFiles = 0;
	let totalOriginal = 0;
	let totalMinified = 0;
	
	allPatterns.forEach(pattern => {
		const files = glob.sync(pattern, { nodir: true });
		
		files.forEach(file => {
			// Überspringe dist/ Ordner und .min.css Dateien
			if (file.includes('/dist/') || file.endsWith('.min.css')) {
				return;
			}
			
			const result = minifyFile(file);
			if (result) {
				totalFiles++;
				totalOriginal += result.originalSize;
				totalMinified += result.minifiedSize;
				console.log(`  ✓ ${result.input}`);
				console.log(`    → ${result.output} (-${result.reduction}%)`);
			}
		});
	});
	
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
		console.log('  Keine CSS-Dateien gefunden.\n');
	}
}

// Script ausführen
build();
