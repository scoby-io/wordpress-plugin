const regEx = new RegExp(/Version: ([\d]\.[\d]\.[\d])/gi);

module.exports.readVersion = function (contents) {
    const [, version] = regEx.exec(contents);
    return version
}

module.exports.writeVersion = function (contents, version) {
    return contents.replace(regEx, `Version: ${version}`)
}