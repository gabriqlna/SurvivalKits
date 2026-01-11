# 📦 SurvivalKits+
**Sistema de Kits de Elite para PocketMine-MP (API 5.x)**

SurvivalKits+ é um plugin modular e de alto desempenho projetado para servidores SMP (Survival Multiplayer). Ele permite a criação de kits totalmente configuráveis via interface visual (GUI), com controle rígido de permissões via PurePerms e exibição de cooldowns em tempo real no ScoreHud.

---

## 🚀 Funcionalidades Principais

* **💎 Kits Totalmente Customizáveis:** Defina itens (com suporte a nomes e quantidades), buffs de efeitos (Speed, Força, etc.) e ícones para cada kit.
* **⏳ Sistema de Cooldown Inteligente:** Cooldowns individuais por kit que persistem mesmo após o reinício do servidor (salvamento em JSON).
* **🖥️ Interface Visual (GUI):** Menu interativo construído com FormAPI, mostrando o status de cada kit (Disponível, Em Cooldown ou Bloqueado por Permissão).
* **🛡️ Integração com PurePerms:** Controle quem pode pegar cada kit com base em grupos e permissões específicas de forma nativa.
* **📊 Suporte a ScoreHud & PAPI:** Integração via PlaceholderAPI para exibir o tempo restante dos kits diretamente na Scoreboard.
* **📈 Progressão por XP:** Sistema opcional de bloqueio de kits por nível de experiência (XP Level).
* **⚡ Performance Otimizada:** Código modular com separação de lógica (Manager) e utilitários (TimeUtils), garantindo baixo consumo de memória.

---

## 🛠️ Instalação

1. Certifique-se de ter as dependências instaladas no seu servidor:
   * **[FormAPI](https://poggit.pmmp.io/p/FormAPI)** (Necessário para os menus).
   * **[PurePerms](https://poggit.pmmp.io/p/PurePerms)** (Para gestão de grupos e permissões).
   * **[PlaceholderAPI](https://poggit.pmmp.io/p/PlaceholderAPI)** (Opcional, para ScoreHud).
2. Coloque o arquivo `SurvivalKits.phar` na pasta `/plugins/`.
3. Reinicie o servidor para gerar os arquivos de configuração.
4. Configure seus kits e mensagens no arquivo `config.yml`.

---

## 📝 Exemplo de Configuração (config.yml)

```yaml
# Exemplo de Kit Guerreiro
kits:
  warrior:
    name: "§6Guerreiro"
    description: "Kit focado em combate corpo a corpo."
    icon: "textures/items/iron_sword"
    cooldown: 3600 # 1 hora em segundos
    permission: "kit.warrior"
    unlock-level: 5 # Requer nível 5 de XP para resgatar
    items:
      - "minecraft:iron_sword:1"
      - "minecraft:cooked_beef:16"
      - "minecraft:iron_helmet:1"
    buffs:
      strength: 1
      speed: 1
      duration: 60 # Duração dos efeitos em segundos
