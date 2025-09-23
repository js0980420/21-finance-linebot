# Using Gemini CLI for Large Codebase Analysis



When analyzing large codebases or multiple files that might exceed context limits, use the Gemini CLI with its massive

context window. Use `gemini -p` to leverage Google Gemini's large context capacity.



## File and Directory Inclusion Syntax



Use the `@` syntax to include files and directories in your Gemini prompts. The paths should be relative to WHERE you run the

  gemini command:



### Examples:



**Single file analysis:**

gemini -p "@src/main.py Explain this file's purpose and structure"



Multiple files:

gemini -p "@package.json @src/index.js Analyze the dependencies used in the code"



Entire directory:

gemini -p "@src/ Summarize the architecture of this codebase"



Multiple directories:

gemini -p "@src/ @tests/ Analyze test coverage for the source code"



Current directory and subdirectories:

gemini -p "@./ Give me an overview of this entire project"



# Or use --all_files flag:

gemini --all_files -p "Analyze the project structure and dependencies"



Implementation Verification Examples



Check if a feature is implemented:

gemini -p "@src/ @lib/ Has dark mode been implemented in this codebase? Show me the relevant files and functions"



Verify authentication implementation:

gemini -p "@src/ @middleware/ Is JWT authentication implemented? List all auth-related endpoints and middleware"



Check for specific patterns:

gemini -p "@src/ Are there any React hooks that handle WebSocket connections? List them with file paths"



Verify error handling:

gemini -p "@src/ @api/ Is proper error handling implemented for all API endpoints? Show examples of try-catch blocks"



Check for rate limiting:

gemini -p "@backend/ @middleware/ Is rate limiting implemented for the API? Show the implementation details"



Verify caching strategy:

gemini -p "@src/ @lib/ @services/ Is Redis caching implemented? List all cache-related functions and their usage"



Check for specific security measures:

gemini -p "@src/ @api/ Are SQL injection protections implemented? Show how user inputs are sanitized"



Verify test coverage for features:

gemini -p "@src/payment/ @tests/ Is the payment processing module fully tested? List all test cases"



When to Use Gemini CLI



Use gemini -p when:

- Analyzing entire codebases or large directories

- Comparing multiple large files

- Need to understand project-wide patterns or architecture

- Current context window is insufficient for the task

- Working with files totaling more than 100KB

- Verifying if specific features, patterns, or security measures are implemented

- Checking for the presence of certain coding patterns across the entire codebase



Important Notes



- Paths in @ syntax are relative to your current working directory when invoking gemini

- The CLI will include file contents directly in the context

- No need for --yolo flag for read-only analysis

- Gemini's context window can handle entire codebases that would overflow Claude's context

- When checking implementations, be specific about what you're looking for to get accurate results

- 端口被占用則殺掉舊程式，盡量不要換端口
- 總是回應我繁體中文不要英文
- 千萬不能gith push到develop分支:https://github.com/13g7895123/finance0810/tree/develop，這是夥伴的生產環境
修改都push到js098分支，test分支已經複製好develop分支，在test分支測試merge分支js098的效果，所以會用test來做CI/CD，部屬到另一個生產環境

🎯 安全工作流程確認

  分支架構

  1. js098 分支 ✅ - 您的本地開發分支（當前所在）
    - 包含您的所有本地修改和新功能
    - 安全推送目標: git push origin js098
  2. test 分支 ✅ - 測試整合分支
    - 已成功複製 partner/develop 的內容
    - 用於測試 js098 分支的合併效果
    - 將用於 CI/CD 部署到您的生產環境
  3. partner/develop 分支 ⚠️ 絕對禁止推送
    - 夥伴的生產環境：https://github.com/13g7895123/finance0810/tree/develop
    - 只能讀取，絕不能修改

  安全操作指南

  ✅ 允許的操作:
  - git push origin js098 - 推送您的修改到 js098 分支
  - git checkout test 然後 git merge js098 - 在 test 分支測試合併
  - 在 test 分支進行 CI/CD 部署

  ❌ 絕對禁止的操作:
  - git push partner develop
  - git push origin develop（如果存在的話）
  - 任何直接修改 partner/develop 的操作

測試帳密
admin
password