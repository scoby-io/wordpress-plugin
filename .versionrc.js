
const readme = {
    filename: 'readme.txt',
    updater: __dirname + '/src/versioning/readme-updater.js'
}
const plugin = {
    filename: 'scoby-analytics.php',
    updater: __dirname + '/src/versioning/plugin-updater.js'
}

const package = {
    filename: 'package.json',
}

const package_lock = {
    filename: 'package-lock.json',
}

module.exports = {
    bumpFiles: [readme, plugin, package, package_lock],
    // packageFiles: [readme]
}
