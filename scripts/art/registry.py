"""فهرست همه تصویرها: نام ← (viewBox، لایه آبرنگ، بدنه). هر نام فقط یک جای سایت."""
ART = {}


def art(name, viewbox='0 0 260 200'):
    def deco(fn):
        assert name not in ART, f'duplicate art name {name}'
        washes, body = fn()
        ART[name] = (viewbox, washes, body)
        return fn
    return deco
