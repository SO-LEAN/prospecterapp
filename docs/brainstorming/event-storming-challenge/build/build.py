import base64, json, pathlib, sys
here = pathlib.Path(sys.argv[1])
model = json.loads(pathlib.Path(sys.argv[2]).read_text())
src = (here / 'build/mockup.src.html').read_text()
font = base64.b64encode((here / 'font/ph.woff2').read_bytes()).decode()
fixture = json.loads((here / 'build/fixture.json').read_text())
out = src.replace('__FONT__', font).replace('__FIXTURE__', json.dumps(fixture)).replace('__MODEL__', json.dumps(model, ensure_ascii=False, indent=None).replace('</', '<\\/'))
(here / 'mockup.html').write_text(out)
print(len(out))
