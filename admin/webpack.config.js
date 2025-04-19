module.exports = {
	entry: {
		'/': './edit.js'
	},
	output: {
		path: __dirname,
		filename: 'edit.min.js',
	},
	module: {
		rules: [
			{
				test: /\.(js|jsx)$/,
				use: { 
					loader: "babel-loader",
				},
				exclude: /(node_modules|bower_components)/,
			}
		]
	}
};
