/* eslint-disable no-console */
const fs = require('fs');
const path = require('path');

const packageJsonPath = path.resolve(__dirname, '..', 'package.json');
const packageJson = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));

const requiredScripts = [
    'dev:anonymous',
    'dev:signedin:10',
    'dev:signedin:25',
    'dev:signedin:50',
    'dev:signedin:100'
];

const requiredFlags = [
    'ENABLE_ADOBE_EVENTS',
    'ENABLE_STOREFRONT_REVAMP',
    'ENABLE_SIGNED_IN_PERSONALIZATION',
    'SIGNED_IN_PERSONALIZATION_ROLLOUT',
    'ADOBE_EVENT_SAMPLE_RATE'
];

const missingScripts = requiredScripts.filter(
    script => !packageJson.scripts || !packageJson.scripts[script]
);

if (missingScripts.length) {
    console.error('❌ Missing rollout scripts:', missingScripts.join(', '));
    process.exit(1);
}

const missingFlagsByScript = [];

for (const scriptName of requiredScripts) {
    const scriptValue = packageJson.scripts[scriptName];
    const missing = requiredFlags.filter(flag => !scriptValue.includes(flag));

    if (missing.length) {
        missingFlagsByScript.push({ scriptName, missing });
    }
}

if (missingFlagsByScript.length) {
    console.error('❌ Rollout script flag checks failed:');
    missingFlagsByScript.forEach(({ scriptName, missing }) => {
        console.error(`  - ${scriptName} missing: ${missing.join(', ')}`);
    });
    process.exit(1);
}

console.log('✅ Rollout smoke check passed');
console.log('Verified scripts:', requiredScripts.join(', '));
console.log('Verified flags:', requiredFlags.join(', '));
console.log('Next steps:');
console.log('  1) npm run dev:anonymous');
console.log('  2) npm run dev:signedin:10');
console.log('  3) promote to 25/50/100 after KPI stability');
