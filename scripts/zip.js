const fs = require('fs');
const pkg = require('../package.json');
const version = pkg.version;
const name = pkg.name;
const archiveName = `${name}-${version.replace(
  /\./g,
  '-'
)}-${new Date().getTime()}`;

const archiver = require('archiver');
const output = fs.createWriteStream(`${archiveName}.zip`);
const archive = archiver('zip', {
  zlib: { level: 9 },
});

output.on('close', () =>
  console.log(
    'archiver has been finalized and the output file descriptor has closed.'
  )
);
output.on('end', () => console.log('Data has been drained'));
archive.on('warning', (err) => {
  if (err.code === 'ENOENT') {
    console.log(err);
  } else {
    throw err;
  }
});
archive.on('error', (err) => {
  throw err;
});

archive.pipe(output);

archive.file('git-installer.php', { name: 'git-installer.php' });
archive.directory('assets/dist/', true);
archive.directory('src/', true);

archive
  .finalize()
  .then((resp) => console.log(resp))
  .catch((e) => console.error(e));
