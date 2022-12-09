const regEx = new RegExp(/Stable tag: ([\d]\.[\d]\.[\d])/gi);

module.exports.readVersion = function (contents) {
    const [, version] = regEx.exec(contents);
    return version
}

module.exports.writeVersion = function (contents, version) {
    return contents.replace(regEx, `Stable tag: ${version}`)
}


module.exports.isPrivate = function () {
    return false
}